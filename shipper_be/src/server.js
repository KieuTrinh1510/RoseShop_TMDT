require("dotenv").config();

const app = require("./app");
const pool = require("./config/database");

const PORT = process.env.PORT || 3000;

app.listen(PORT, async () => {
    console.log(`Server running at http://localhost:${PORT}`);

    try {
        await pool.query("SELECT 1");

        console.log("Database connected successfully!");
    } catch (error) {
        console.error("Database connection failed!");
        console.error(error.message);
    }
});