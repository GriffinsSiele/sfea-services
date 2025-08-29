import os
import re
import sys
from pathlib import Path
from typing import List

sys.path.insert(0, ".")
sys.path.insert(0, "..")

bumpconfig = "../.bumpversion.cfg"
data = open(bumpconfig).read().splitlines() if Path(bumpconfig).exists() else []
release = None

for line in data:
    match = re.findall("current_version = (.*)", line)
    if match:
        release = match[0]

release = release if release else ""


autodoc_mock_imports = ["async_timeout", "selenium", "undetected_chromedriver"]

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
exclude_patterns: List[str] = ["tests.rst", "setup.rst"]

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
