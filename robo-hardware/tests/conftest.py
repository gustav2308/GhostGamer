"""
Fixtures compartilhadas dos testes.

O arquivo da API se chama "05_api.py" -- um nome de arquivo começando
com dígito não pode ser importado com "import 05_api" (SyntaxError em
Python), então usamos importlib.import_module(), que carrega o módulo
pelo nome do arquivo sem essa restrição.
"""

import importlib
import sys
from pathlib import Path

import pytest
from fastapi.testclient import TestClient

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

api = importlib.import_module("05_api")


@pytest.fixture()
def client():
    with TestClient(api.app) as c:
        yield c
