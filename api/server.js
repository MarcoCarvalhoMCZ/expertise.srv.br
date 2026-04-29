require('dotenv').config();
const express = require('express');
const cors = require('cors');
const cookieParser = require('cookie-parser');
const path = require('path');

const authRoutes = require('./routes/auth');
const postRoutes = require('./routes/posts');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware globais
app.use(cors({
  origin: true,
  credentials: true
}));
app.use(cookieParser());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

// Servir uploads de mídia
app.use('/api/uploads', express.static(path.join(__dirname, 'uploads')));

// Rotas da API
app.use('/api/auth', authRoutes);
app.use('/api/posts', postRoutes);

// Painel admin (SPA servida como estático)
app.use('/admin', express.static(path.join(__dirname, '..', 'admin')));

// Site principal — servir arquivos estáticos da raiz
app.use(express.static(path.join(__dirname, '..')));

// Iniciar servidor
app.listen(PORT, () => {
  console.log(`Servidor rodando em http://localhost:${PORT}`);
  console.log(`Painel admin em http://localhost:${PORT}/admin`);
  console.log(`API em http://localhost:${PORT}/api`);
});