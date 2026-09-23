const mysql = require("mysql2/promise");
const fs = require("fs");
const pool = mysql.createPool({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT),
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE,

    ssl: {
        ca: fs.readFileSync(process.env.MYSQL_ATTR_SSL_CA)
    },
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
})

module.exports = pool;