
# app/routes/review_routes.py
# Mendaftarkan endpoint API review dan mengarahkannya ke controller.

from flask import Blueprint

from app.controllers import review_controller

review_bp = Blueprint("reviews", __name__, url_prefix="/reviews")

# GET /reviews/product/<product_id>
review_bp.add_url_rule(
    "/product/<product_id>",
    view_func=review_controller.list_by_product,
    methods=["GET"],
)

# GET /reviews/<review_id>
review_bp.add_url_rule(
    "/<review_id>",
    view_func=review_controller.show,
    methods=["GET"],
)

# POST /reviews
review_bp.add_url_rule(
    "",
    view_func=review_controller.store,
    methods=["POST"],
)

# PUT /reviews/<review_id>
review_bp.add_url_rule(
    "/<review_id>",
    view_func=review_controller.update,
    methods=["PUT"],
)

# DELETE /reviews/<review_id>
review_bp.add_url_rule(
    "/<review_id>",
    view_func=review_controller.destroy,
    methods=["DELETE"],
)