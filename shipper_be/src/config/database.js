// src/config/database.js
// Kết nối Sequelize (thay cho mysql2 pool)

const { Sequelize } = require('sequelize');
const fs = require('fs');

const sequelize = new Sequelize(
    process.env.DB_DATABASE,
    process.env.DB_USERNAME,
    process.env.DB_PASSWORD,
    {
        host: process.env.DB_HOST,
        port: Number(process.env.DB_PORT),
        dialect: 'mysql',
        dialectOptions: process.env.MYSQL_ATTR_SSL_CA
            ? {
                ssl: {
                    ca: fs.readFileSync(process.env.MYSQL_ATTR_SSL_CA),
                },
            }
            : {},
        pool: {
            max: 10,
            min: 0,
            idle: 10000,
        },
        logging: false, // đổi thành console.log nếu cần debug SQL
        define: {
            // Các bảng trong DB dùng snake_case, không có createdAt/updatedAt
            timestamps: false,
            underscored: true,
        },
    }
);

module.exports = sequelize;