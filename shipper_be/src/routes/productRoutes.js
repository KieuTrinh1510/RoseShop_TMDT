const express = require("express");
const router = express.Router();
//import controller
const { getProducts } = require("../controllers/productController");
// khi client goi Get/product thi chay ham getproduct

router.get("/", getProducts);
module.exports = router;