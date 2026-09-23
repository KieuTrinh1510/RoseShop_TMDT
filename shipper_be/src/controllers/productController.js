const pool = require("../config/database");
// lay danh sach san pham
const getProducts = async (req, res) => {
    try {
        // cau lenh sql
        const sql = ` SELECT 
        ma_san_pham,
        ten_san_pham,
        so_luong,
        loai_san_pham,
        mo_ta,
        hinh_anh,
        trang_thai
        FROM san_pham
        `;

        //chay lenh sql 
        const [rows] = await pool.query(sql);// tra ve mang [rows,fields], rows la du lieu lay dc tu db

        console.log("so san pham la:", rows.length);
        console.log("du lieu:", rows);
        //tra du lieu cho client
        res.status(200).json({
            succes: true,
            total: rows.length,
            data: rows
        });

    } catch (error) {
        console.error("loi khi lay san pham:", error.message);

        res.status(500).json({
            success: false,
            message: "khong the lay du lieu"
        });
    }

};
module.exports = {
    getProducts
};