const express = require('express');
const cors = require('cors');
const productRoutes = require('./routes/productRoutes'); 

const app = express();

app.use(cors());
app.use(express.json({ limit: '2mb' }));

// endpoint for health check
app.get('/health', (req, res) => {
    res.json({ 
        status: "ok",
        service: "product-service"
    });
});

app.use("/products", productRoutes);

// Error handler untuk ukuran request terlalu besar
app.use((err, req, res, next) => {
    if (err.type === 'entity.too.large') {
        return res.status(400).json({
            message: "Gambar terlalu besar. Maksimal ukuran 2 MB."
        });
    }

    res.status(500).json({
        message: "Terjadi kesalahan pada server."
    });
});

//unknown path
app.use((req, res) => {
    res.status(404).json({
        message: "Endpoint tidak dikenal"
    });
});

module.exports = app;