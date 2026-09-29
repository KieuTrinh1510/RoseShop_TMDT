const express = require("express");
const cors = require("cors");
const app = express();


const shipperRoutes = require("./routes/shipperRoutes");
const fakeAuthMiddleware = require("./middleware/fakeAuthMiddleware");

app.use(cors());
app.use(express.json());


app.use("/api/shipper", fakeAuthMiddleware, shipperRoutes);

module.exports = app;