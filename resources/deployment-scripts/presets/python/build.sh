python -m venv .venv
if [ -f requirements.txt ]; then
  .venv/bin/python -m pip install -r requirements.txt
elif [ -f pyproject.toml ] || [ -f setup.py ]; then
  .venv/bin/python -m pip install .
fi
