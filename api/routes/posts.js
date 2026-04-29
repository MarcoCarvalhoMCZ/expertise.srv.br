const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { query, execute, sql } = require('../db');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Configuração do Multer para upload de mídia
const uploadsDir = path.join(__dirname, '..', 'uploads');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    // Organiza por tipo em subpastas
    let subfolder = 'others';
    if (file.mimetype.startsWith('image/')) subfolder = 'images';
    else if (file.mimetype.startsWith('video/')) subfolder = 'videos';
    else if (file.mimetype.startsWith('audio/')) subfolder = 'audios';
    const dir = path.join(uploadsDir, subfolder);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    cb(null, dir);
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname);
    cb(null, uniqueSuffix + ext);
  }
});

const upload = multer({
  storage,
  limits: { fileSize: 100 * 1024 * 1024 }, // 100MB max
  fileFilter: (req, file, cb) => {
    const allowed = /^(image\/(jpeg|png|gif|webp)|video\/(mp4|webm|ogg)|audio\/(mpeg|ogg|wav|mp4))$/;
    if (allowed.test(file.mimetype)) {
      cb(null, true);
    } else {
      cb(new Error('Tipo de arquivo não permitido. Use imagens, vídeos ou áudios.'));
    }
  }
});

// GET /api/posts — listar posts públicos (ou todos se admin autenticado)
router.get('/', async (req, res) => {
  try {
    let filter = 'WHERE p.published = 1';
    // Se tiver token válido, pode ver também os não publicados
    const authHeader = req.headers.authorization;
    const jwt = require('jsonwebtoken');
    if (authHeader && authHeader.startsWith('Bearer ')) {
      try {
        const decoded = jwt.verify(authHeader.slice(7), process.env.JWT_SECRET);
        if (decoded) filter = '';
      } catch (_) { /* token inválido, mantém filtro público */ }
    }

    const result = await query(`
      SELECT p.id, p.title, p.slug, p.content, p.post_type, p.media_url,
             p.thumbnail_url, p.excerpt, p.published, p.featured,
             p.created_at, p.updated_at,
             u.display_name AS author_name
      FROM posts p
      LEFT JOIN users u ON p.author_id = u.id
      ${filter}
      ORDER BY p.created_at DESC
    `);

    const posts = result.recordset;

    // Buscar mídias associadas para cada post
    for (const post of posts) {
      const mediaResult = await query(
        'SELECT id, media_type, file_url, file_name, file_size, sort_order FROM post_media WHERE post_id = $1 ORDER BY sort_order',
        [post.id]
      );
      post.media = mediaResult.recordset;
    }

    res.json({ posts });
  } catch (err) {
    console.error('Erro ao listar posts:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// GET /api/posts/:slug — post individual por slug
router.get('/:slug', async (req, res) => {
  try {
    const result = await query(
      `SELECT p.*, u.display_name AS author_name
       FROM posts p
       LEFT JOIN users u ON p.author_id = u.id
       WHERE p.slug = $1 AND p.published = 1`,
      [req.params.slug]
    );
    if (result.recordset.length === 0) {
      return res.status(404).json({ error: 'Post não encontrado.' });
    }
    const post = result.recordset[0];
    const mediaResult = await query(
      'SELECT id, media_type, file_url, file_name, file_size, sort_order FROM post_media WHERE post_id = $1 ORDER BY sort_order',
      [post.id]
    );
    post.media = mediaResult.recordset;
    res.json({ post });
  } catch (err) {
    console.error('Erro ao buscar post:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// POST /api/posts — criar novo post (admin autenticado)
router.post('/', authenticateToken, async (req, res) => {
  try {
    const { title, content, post_type, excerpt, published, featured, thumbnail_url } = req.body;
    if (!title) {
      return res.status(400).json({ error: 'Título é obrigatório.' });
    }

    // Gerar slug a partir do título
    const slug = title
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      + '-' + Date.now();

    const result = await execute(
      `INSERT INTO posts (title, slug, content, post_type, excerpt, published, featured, thumbnail_url, author_id)
       OUTPUT INSERTED.id
       VALUES (@title, @slug, @content, @post_type, @excerpt, @published, @featured, @thumbnail_url, @author_id)`,
      {
        title: { type: sql.NVarChar(255), value: title },
        slug: { type: sql.NVarChar(300), value: slug },
        content: { type: sql.NVarChar(sql.MAX), value: content || '' },
        post_type: { type: sql.NVarChar(20), value: post_type || 'text' },
        excerpt: { type: sql.NVarChar(500), value: excerpt || null },
        published: { type: sql.Bit, value: published ? 1 : 0 },
        featured: { type: sql.Bit, value: featured ? 1 : 0 },
        thumbnail_url: { type: sql.NVarChar(500), value: thumbnail_url || null },
        author_id: { type: sql.Int, value: req.user.id }
      }
    );

    const newId = result.recordset[0].id;
    res.status(201).json({ message: 'Post criado com sucesso.', id: newId, slug });
  } catch (err) {
    console.error('Erro ao criar post:', err);
    if (err.originalError && err.originalError.message.includes('UNIQUE')) {
      return res.status(400).json({ error: 'Já existe um post com esse título. Tente um título diferente.' });
    }
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// PUT /api/posts/:id — atualizar post (admin)
router.put('/:id', authenticateToken, async (req, res) => {
  try {
    const postId = parseInt(req.params.id, 10);
    const { title, content, post_type, excerpt, published, featured, thumbnail_url } = req.body;

    const result = await execute(
      `UPDATE posts SET
        title = COALESCE(@title, title),
        content = COALESCE(@content, content),
        post_type = COALESCE(@post_type, post_type),
        excerpt = @excerpt,
        published = COALESCE(@published, published),
        featured = COALESCE(@featured, featured),
        thumbnail_url = @thumbnail_url,
        updated_at = GETDATE()
       WHERE id = @id`,
      {
        title: { type: sql.NVarChar(255), value: title || null },
        content: { type: sql.NVarChar(sql.MAX), value: content !== undefined ? content : null },
        post_type: { type: sql.NVarChar(20), value: post_type || null },
        excerpt: { type: sql.NVarChar(500), value: excerpt !== undefined ? excerpt : undefined },
        published: { type: sql.Bit, value: published !== undefined ? (published ? 1 : 0) : undefined },
        featured: { type: sql.Bit, value: featured !== undefined ? (featured ? 1 : 0) : undefined },
        thumbnail_url: { type: sql.NVarChar(500), value: thumbnail_url !== undefined ? thumbnail_url : undefined },
        id: { type: sql.Int, value: postId }
      }
    );

    if (result.rowsAffected[0] === 0) {
      return res.status(404).json({ error: 'Post não encontrado.' });
    }
    res.json({ message: 'Post atualizado com sucesso.' });
  } catch (err) {
    console.error('Erro ao atualizar post:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// DELETE /api/posts/:id — deletar post (admin)
router.delete('/:id', authenticateToken, async (req, res) => {
  try {
    const postId = parseInt(req.params.id, 10);

    // Excluir mídias associadas primeiro (CASCADE no banco já faz isso, mas vamos garantir)
    await execute('DELETE FROM post_media WHERE post_id = @id', {
      id: { type: sql.Int, value: postId }
    });
    const result = await execute('DELETE FROM posts WHERE id = @id', {
      id: { type: sql.Int, value: postId }
    });

    if (result.rowsAffected[0] === 0) {
      return res.status(404).json({ error: 'Post não encontrado.' });
    }
    res.json({ message: 'Post excluído com sucesso.' });
  } catch (err) {
    console.error('Erro ao deletar post:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// POST /api/posts/:id/media — upload de mídia para um post (admin)
router.post('/:id/media', authenticateToken, upload.array('files', 10), async (req, res) => {
  try {
    const postId = parseInt(req.params.id, 10);

    // Verificar se o post existe
    const postCheck = await query('SELECT id, post_type FROM posts WHERE id = $1', [postId]);
    if (postCheck.recordset.length === 0) {
      return res.status(404).json({ error: 'Post não encontrado.' });
    }

    if (!req.files || req.files.length === 0) {
      return res.status(400).json({ error: 'Nenhum arquivo enviado.' });
    }

    const mediaRecords = [];
    for (const file of req.files) {
      let mediaType = 'image';
      if (file.mimetype.startsWith('video/')) mediaType = 'video';
      else if (file.mimetype.startsWith('audio/')) mediaType = 'audio';

      const fileUrl = '/api/uploads/' + path.relative(uploadsDir, file.path).replace(/\\/g, '/');

      const result = await execute(
        `INSERT INTO post_media (post_id, media_type, file_url, file_name, file_size)
         OUTPUT INSERTED.id
         VALUES (@post_id, @media_type, @file_url, @file_name, @file_size)`,
        {
          post_id: { type: sql.Int, value: postId },
          media_type: { type: sql.NVarChar(20), value: mediaType },
          file_url: { type: sql.NVarChar(500), value: fileUrl },
          file_name: { type: sql.NVarChar(255), value: file.originalname },
          file_size: { type: sql.BigInt, value: file.size }
        }
      );
      mediaRecords.push({ id: result.recordset[0].id, file_url: fileUrl, media_type: mediaType });
    }

    // Atualizar post_type se necessário (se tem mídia, não é só texto)
    await execute(
      `UPDATE posts SET post_type = 'mixed', updated_at = GETDATE() WHERE id = @id`,
      { id: { type: sql.Int, value: postId } }
    );

    res.status(201).json({ message: `${req.files.length} arquivo(s) enviado(s).`, media: mediaRecords });
  } catch (err) {
    console.error('Erro no upload:', err);
    res.status(500).json({ error: 'Erro no upload de arquivos.' });
  }
});

// DELETE /api/posts/:postId/media/:mediaId — deletar mídia específica (admin)
router.delete('/:postId/media/:mediaId', authenticateToken, async (req, res) => {
  try {
    const postId = parseInt(req.params.postId, 10);
    const mediaId = parseInt(req.params.mediaId, 10);

    // Buscar arquivo antes de deletar
    const mediaResult = await query(
      'SELECT id, file_url FROM post_media WHERE id = $1 AND post_id = $2',
      [mediaId, postId]
    );
    if (mediaResult.recordset.length === 0) {
      return res.status(404).json({ error: 'Mídia não encontrada.' });
    }

    // Tentar remover arquivo físico
    const fileUrl = mediaResult.recordset[0].file_url;
    const filePath = path.join(__dirname, '..', fileUrl.replace('/api/', ''));
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }

    await execute('DELETE FROM post_media WHERE id = @id', {
      id: { type: sql.Int, value: mediaId }
    });

    res.json({ message: 'Mídia excluída com sucesso.' });
  } catch (err) {
    console.error('Erro ao deletar mídia:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

module.exports = router;