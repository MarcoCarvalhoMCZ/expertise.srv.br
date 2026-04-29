require('dotenv').config();
const sql = require('mssql');

const config = {
  server: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT, 10) || 11433,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_DATABASE,
  options: {
    encrypt: false,
    trustServerCertificate: true,
    enableArithAbort: true
  }
};

async function setupDatabase() {
  try {
    console.log('Conectando ao SQL Server...');
    const pool = await sql.connect(config);
    console.log('Conectado. Criando tabelas...');

    // Tabela de usuários (admin)
    await pool.request().query(`
      IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='users' AND xtype='U')
      CREATE TABLE users (
        id INT IDENTITY(1,1) PRIMARY KEY,
        username NVARCHAR(100) NOT NULL UNIQUE,
        password_hash NVARCHAR(255) NOT NULL,
        display_name NVARCHAR(150) NULL,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
      )
    `);
    console.log('Tabela [users] verificada/criada.');

    // Tabela de posts do blog
    await pool.request().query(`
      IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='posts' AND xtype='U')
      CREATE TABLE posts (
        id INT IDENTITY(1,1) PRIMARY KEY,
        title NVARCHAR(255) NOT NULL,
        slug NVARCHAR(300) NOT NULL UNIQUE,
        content NVARCHAR(MAX) NULL,
        post_type NVARCHAR(20) DEFAULT 'text' CHECK (post_type IN ('text','image','video','audio','mixed')),
        media_url NVARCHAR(500) NULL,
        thumbnail_url NVARCHAR(500) NULL,
        excerpt NVARCHAR(500) NULL,
        author_id INT REFERENCES users(id),
        published BIT DEFAULT 0,
        featured BIT DEFAULT 0,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
      )
    `);
    console.log('Tabela [posts] verificada/criada.');

    // Tabela de mídia (múltiplos arquivos por post)
    await pool.request().query(`
      IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='post_media' AND xtype='U')
      CREATE TABLE post_media (
        id INT IDENTITY(1,1) PRIMARY KEY,
        post_id INT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        media_type NVARCHAR(20) NOT NULL CHECK (media_type IN ('image','video','audio','document')),
        file_url NVARCHAR(500) NOT NULL,
        file_name NVARCHAR(255) NULL,
        file_size BIGINT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT GETDATE()
      )
    `);
    console.log('Tabela [post_media] verificada/criada.');

    // Inserir usuário admin padrão (senha: admin123)
    const bcrypt = require('bcryptjs');
    const hash = await bcrypt.hash('admin123', 10);
    await pool.request()
      .input('username', sql.NVarChar(100), 'admin')
      .input('password_hash', sql.NVarChar(255), hash)
      .input('display_name', sql.NVarChar(150), 'Administrador')
      .query(`
        IF NOT EXISTS (SELECT 1 FROM users WHERE username = @username)
        INSERT INTO users (username, password_hash, display_name)
        VALUES (@username, @password_hash, @display_name)
      `);
    console.log('Usuário admin padrão verificado/criado (username: admin, senha: admin123).');

    await pool.close();
    console.log('Setup do banco de dados concluído com sucesso!');
    process.exit(0);
  } catch (err) {
    console.error('Erro no setup do banco de dados:', err.message);
    process.exit(1);
  }
}

setupDatabase();