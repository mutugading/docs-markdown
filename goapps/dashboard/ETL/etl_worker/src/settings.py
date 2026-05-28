"""
Config loader. YAML file + environment variable overrides.

Env var convention: ETL_<SECTION>_<KEY> (uppercase)
  - ETL_ORACLE_PASS overrides config['oracle']['password']
  - ETL_PG_HOST overrides config['postgres']['host']
"""
import os
import yaml
from pathlib import Path


# Map env var names to config paths (kept explicit for safety — never auto-walk
# the YAML tree based on env names, that lets attackers inject arbitrary keys)
ENV_OVERRIDES = {
    "ETL_ORACLE_HOST": ("oracle", "host"),
    "ETL_ORACLE_PORT": ("oracle", "port"),
    "ETL_ORACLE_SERVICE": ("oracle", "service_name"),
    "ETL_ORACLE_USER": ("oracle", "user"),
    "ETL_ORACLE_PASS": ("oracle", "password"),
    "ETL_ORACLE_SCHEMA": ("oracle", "schema"),
    "ETL_PG_HOST": ("postgres", "host"),
    "ETL_PG_PORT": ("postgres", "port"),
    "ETL_PG_DB": ("postgres", "database"),
    "ETL_PG_USER": ("postgres", "user"),
    "ETL_PG_PASS": ("postgres", "password"),
    "ETL_SLACK_WEBHOOK": ("notification", "slack_webhook_url"),
    "ETL_LOG_LEVEL": ("logging", "level"),
    "ETL_REDIS_HOST": ("redis", "host"),
    "ETL_REDIS_PASS": ("redis", "password"),
}


def load_config(config_path=None):
    """Load YAML config and apply environment variable overrides."""
    if config_path is None:
        # Default location relative to project root
        config_path = Path(__file__).parent.parent / "config" / "config.yaml"
    config_path = Path(config_path)
    if not config_path.exists():
        raise FileNotFoundError(
            f"Config not found: {config_path}. "
            "Copy config/config.example.yaml to config/config.yaml first."
        )

    with open(config_path) as f:
        config = yaml.safe_load(f)

    # Apply env var overrides
    for env_var, path in ENV_OVERRIDES.items():
        val = os.environ.get(env_var)
        if val is not None:
            section, key = path
            if section not in config:
                config[section] = {}
            # Cast numeric env vars to int
            if key in ("port",):
                try:
                    val = int(val)
                except ValueError:
                    pass
            config[section][key] = val

    _validate(config)
    return config


def _validate(config):
    """Sanity-check required fields. Fails fast with clear message."""
    required = [
        ("oracle", "host"), ("oracle", "user"), ("oracle", "password"),
        ("oracle", "service_name"),
        ("postgres", "host"), ("postgres", "user"), ("postgres", "password"),
        ("postgres", "database"),
        ("source_code",), ("jobs",),
    ]
    for path in required:
        node = config
        for key in path:
            if not isinstance(node, dict) or key not in node:
                raise ValueError(f"Missing required config: {'.'.join(path)}")
            node = node[key]
        if node in (None, "", "CHANGE_ME"):
            raise ValueError(f"Config {'.'.join(path)} not set (still placeholder?)")
