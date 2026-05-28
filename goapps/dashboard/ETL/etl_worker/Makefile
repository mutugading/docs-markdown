# =====================================================================
# ETL Worker — Developer Makefile
# =====================================================================

.PHONY: install test test-unit test-e2e dry-run run lint clean

install:
	python3 -m venv venv
	./venv/bin/pip install --upgrade pip
	./venv/bin/pip install -r requirements.txt
	./venv/bin/pip install pytest pytest-cov

test: test-unit test-e2e

test-unit:
	./venv/bin/python -m pytest tests/test_transformer.py -v

test-e2e:
	./venv/bin/python tests/test_e2e_simulation.py

test-connection:
	./venv/bin/python scripts/test_connection.py

dry-run:
	./venv/bin/python scripts/dry_run.py --job ETL_MIS_EBITDA

run:
	./venv/bin/python -m src.main --job ETL_MIS_EBITDA --triggered-by MANUAL

lint:
	./venv/bin/python -m pyflakes src/ scripts/ tests/

clean:
	rm -rf venv __pycache__ .pytest_cache
	find . -type d -name __pycache__ -exec rm -rf {} +
	find . -type f -name '*.pyc' -delete

help:
	@echo "Available targets:"
	@echo "  install         — Create venv and install dependencies"
	@echo "  test            — Run all tests (unit + e2e)"
	@echo "  test-unit       — Run transformer unit tests"
	@echo "  test-e2e        — Run end-to-end transform simulation"
	@echo "  test-connection — Test Oracle + Postgres connectivity"
	@echo "  dry-run         — Execute ETL without writing to Postgres"
	@echo "  run             — Run ETL job ETL_MIS_EBITDA (manual trigger)"
	@echo "  lint            — Static check with pyflakes"
	@echo "  clean           — Remove venv and cache"
