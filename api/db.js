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
  },
  pool: {
    max: 10,
    min: 0,
    idleTimeoutMillis: 30000
  }
};

let pool = null;

async function getPool() {
  if (pool) return pool;
  pool = await sql.connect(config);
  console.log('Conectado ao SQL Server:', config.server);
  return pool;
}

async function query(text, params = []) {
  const p = await getPool();
  const request = p.request();
  params.forEach((param, index) => {
    request.input(`p${index}`, param);
  });
  // Replace $1, $2 style params with @pN for mssql
  let mssqlText = text;
  params.forEach((_, index) => {
    mssqlText = mssqlText.replace(`$${index + 1}`, `@p${index}`);
  });
  return request.query(mssqlText);
}

async function execute(text, paramMap = {}) {
  const p = await getPool();
  const request = p.request();
  Object.entries(paramMap).forEach(([key, paramDef]) => {
    if (paramDef && paramDef.type && paramDef.value !== undefined) {
      request.input(key, paramDef.type, paramDef.value);
    } else if (paramDef && paramDef.type) {
      request.input(key, paramDef.type, null);
    } else {
      request.input(key, paramDef);
    }
  });
  return request.query(text);
}

module.exports = { getPool, query, execute, sql };