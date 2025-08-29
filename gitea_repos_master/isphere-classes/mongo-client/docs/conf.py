import os
import sys
from typing import List

sys.path.insert(0, ".")
sys.path.insert(0, "..")

autodoc_mock_imports = ["async_timeout"]

project = os.getenv("DRONE_REPO_NAME", "package") + "-docs"
author = os.getenv("DRONE_COMMIT_AUTHOR", "isphere-worker")
copyright = "i-sphere.ru"
autosummary_generate = True

autodoc_member_order = "bysource"
root_doc = "modules"
html_theme = "sphinx_rtd_theme"

autodoc_default_options = {
    "members": True,
    "show-inheritance": True,
}

python_maximum_signature_line_length = 100

templates_path: List[str] = ["_templates"]
exclude_patterns: List[str] = []

language = "ru"

extensions = [
    "myst_parser",
    "sphinx.ext.duration",
    "sphinx.ext.doctest",
    "sphinx.ext.viewcode",
    "sphinx.ext.autodoc",
    "sphinx.ext.autosummary",
    "sphinx.ext.napoleon",
    "sphinx_autodoc_typehints",
    "sphinx_markdown_builder",
]
