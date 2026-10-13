// src/server.js
require("dotenv").config(); // phải chạy TRƯỚC khi require database/redis

const app = require("./app");
const sequelize = require("./config/database"); // Sequelize instance (không phải mysql2 pool)

const PORT = process.env.PORT || 3000;

app.listen(PORT, async () => {
    console.log(`Server running at http://localhost:${PORT}`);

    try {
        await sequelize.authenticate();
        console.log("Database connected successfully!");
    } catch (error) {
        console.error("Database connection failed!");
        console.error(error.message);
    }
});