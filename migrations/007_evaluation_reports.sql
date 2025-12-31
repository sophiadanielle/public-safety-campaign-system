-- Evaluation reports table to store generated impact reports (HTML/PDF paths)
USE LGU;

CREATE TABLE IF NOT EXISTS `campaign_department_evaluation_reports` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    snapshot_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_eval_reports_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE
) ENGINE=InnoDB;





