const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { query } = require('../db');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// POST /api/auth/login
router.post('/login', async (req, res) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) {
      return res.status(400).json({ error: 'Usuário e senha são obrigatórios.' });
    }

    const result = await query(
      'SELECT id, username, password_hash, display_name FROM users WHERE username = $1',
      [username]
    );

    if (result.recordset.length === 0) {
      return res.status(401).json({ error: 'Usuário ou senha inválidos.' });
    }

    const user = result.recordset[0];
    const valid = await bcrypt.compare(password, user.password_hash);
    if (!valid) {
      return res.status(401).json({ error: 'Usuário ou senha inválidos.' });
    }

    const token = jwt.sign(
      { id: user.id, username: user.username },
      process.env.JWT_SECRET,
      { expiresIn: '8h' }
    );

    // Cookie HTTP-only
    res.cookie('token', token, {
      httpOnly: true,
      secure: false, // true em produção com HTTPS
      sameSite: 'lax',
      maxAge: 8 * 60 * 60 * 1000 // 8h
    });

    res.json({
      message: 'Login realizado com sucesso.',
      user: {
        id: user.id,
        username: user.username,
        display_name: user.display_name
      },
      token // também retorna pra uso em header se necessário
    });
  } catch (err) {
    console.error('Erro no login:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// GET /api/auth/me — verificar sessão atual
router.get('/me', authenticateToken, async (req, res) => {
  try {
    const result = await query(
      'SELECT id, username, display_name, created_at FROM users WHERE id = $1',
      [req.user.id]
    );
    if (result.recordset.length === 0) {
      return res.status(404).json({ error: 'Usuário não encontrado.' });
    }
    res.json({ user: result.recordset[0] });
  } catch (err) {
    console.error('Erro ao buscar usuário:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// PUT /api/auth/password — alterar senha (admin autenticado)
router.put('/password', authenticateToken, async (req, res) => {
  try {
    const { current_password, new_password } = req.body;
    if (!current_password || !new_password) {
      return res.status(400).json({ error: 'Senha atual e nova senha são obrigatórias.' });
    }
    if (new_password.length < 6) {
      return res.status(400).json({ error: 'A nova senha deve ter pelo menos 6 caracteres.' });
    }

    // Verificar senha atual
    const result = await query(
      'SELECT password_hash FROM users WHERE id = $1',
      [req.user.id]
    );
    if (result.recordset.length === 0) {
      return res.status(404).json({ error: 'Usuário não encontrado.' });
    }

    const valid = await bcrypt.compare(current_password, result.recordset[0].password_hash);
    if (!valid) {
      return res.status(401).json({ error: 'Senha atual incorreta.' });
    }

    // Atualizar senha
    const hash = await bcrypt.hash(new_password, 10);
    await query(
      'UPDATE users SET password_hash = $1, updated_at = GETDATE() WHERE id = $2',
      [hash, req.user.id]
    );

    res.json({ message: 'Senha alterada com sucesso.' });
  } catch (err) {
    console.error('Erro ao alterar senha:', err);
    res.status(500).json({ error: 'Erro interno no servidor.' });
  }
});

// POST /api/auth/logout
router.post('/logout', (req, res) => {
  res.clearCookie('token');
  res.json({ message: 'Logout realizado com sucesso.' });
});

module.exports = router;
