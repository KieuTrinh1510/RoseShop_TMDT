const express = require("express");
const app = express();

const productRoutes = require("./routes/productRoutes");

// Gắn routes: mọi request bắt đầu bằng /products sẽ vào productRoutes


app.use(express.json());
app.use("/products", productRoutes);
module.exports = app;