"""
Notification module — Slack webhook integration for failure alerts.

Email support is stubbed for Phase 2 (requires SMTP config).
"""
import logging
import socket
from datetime import datetime, timezone

import requests


logger = logging.getLogger(__name__)


class Notifier:
    def __init__(self, notification_config):
        self.cfg = notification_config or {}
        self.enabled = self.cfg.get("enabled", False)
        self.slack_webhook = self.cfg.get("slack_webhook_url", "")

    def notify_failure(self, job_name, error_message, log_id=None):
        if not self.enabled:
            logger.debug("Notification disabled — skipping")
            return
        if not self.slack_webhook:
            logger.warning("Slack webhook not configured — failure not notified")
            return

        host = socket.gethostname()
        timestamp = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S UTC")
        text = self._format_failure(job_name, error_message, log_id, host, timestamp)

        try:
            resp = requests.post(
                self.slack_webhook,
                json={"text": text},
                timeout=10,
            )
            if resp.status_code >= 400:
                logger.warning(
                    "Slack notification returned %d: %s",
                    resp.status_code, resp.text[:200],
                )
            else:
                logger.info("Slack failure notification sent")
        except requests.RequestException as e:
            # Never let notification failure crash the ETL — it's already failing
            logger.warning("Failed to send Slack notification: %s", e)

    def notify_success(self, job_name, rows_affected, duration_sec):
        """Optional — only sent if notify_on_success=true in config."""
        if not self.enabled or not self.cfg.get("notify_on_success", False):
            return
        if not self.slack_webhook:
            return
        text = (
            f":white_check_mark: ETL Success: *{job_name}*\n"
            f"Rows: {rows_affected:,} | Duration: {duration_sec:.1f}s"
        )
        try:
            requests.post(self.slack_webhook, json={"text": text}, timeout=10)
        except requests.RequestException as e:
            logger.warning("Failed to send success notification: %s", e)

    @staticmethod
    def _format_failure(job_name, error_message, log_id, host, timestamp):
        # Truncate long stack traces for Slack
        snippet = (error_message or "")[:1500]
        if len(error_message or "") > 1500:
            snippet += "\n... (truncated, see ETL_JOB_LOG for full)"
        return (
            f":rotating_light: *ETL Job FAILED: {job_name}*\n"
            f"*Host:* `{host}`\n"
            f"*Time:* {timestamp}\n"
            f"*Log ID:* {log_id or 'N/A'}\n"
            f"*Error:*\n```{snippet}```\n"
            f"_Check `ETL_JOB_LOG` table for full details._"
        )
