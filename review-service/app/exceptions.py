
# app/exceptions.py
# Exception khusus untuk menangani masalah yang sudah diperkirakan.


class ValidationError(Exception):
    def __init__(self, errors):
        super().__init__("Data yang dikirim tidak valid")
        self.errors = errors


class ReviewNotFoundError(Exception):
    def __init__(self, review_id):
        super().__init__(f"Review dengan id {review_id} tidak ditemukan")


class DuplicateReviewError(Exception):
    def __init__(self, user_id, product_id):
        super().__init__(
            f"User {user_id} sudah pernah mengulas produk {product_id}"
        )


class ProductNotFoundError(Exception):
    def __init__(self, product_id):
        super().__init__(
            f"Produk dengan id {product_id} tidak ditemukan"
        )


class ProductServiceUnavailableError(Exception):
    pass
