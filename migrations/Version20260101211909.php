<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260101211909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Check if table exists before modifying (table may not exist in all environments)
        $leadCapturesExists = $schema->hasTable('lead_captures');

        $this->addSql('ALTER TABLE account_deletion_requests RENAME INDEX idx_account_deletion_request_company TO idx_accountdeletionrequest_company');
        $this->addSql('ALTER TABLE billing_markers RENAME INDEX idx_billing_marker_company TO idx_billingmarker_company');
        $this->addSql('DROP INDEX company_settings_company_unique ON company_settings');
        $this->addSql('ALTER TABLE company_settings RENAME INDEX idx_company_settings_company TO idx_companysettings_company');
        $this->addSql('ALTER TABLE contributor_progress RENAME INDEX idx_contributor_progress_company TO idx_contributorprogress_company');
        $this->addSql('ALTER TABLE contributor_satisfactions RENAME INDEX idx_contributor_satisfaction_company TO idx_contributorsatisfaction_company');
        $this->addSql('ALTER TABLE contributor_skills RENAME INDEX idx_contributor_skill_company TO idx_contributorskill_company');
        $this->addSql('ALTER TABLE contributor_profiles DROP FOREIGN KEY `fk_contributor_profile_company`');
        $this->addSql('DROP INDEX idx_contributor_profile_company ON contributor_profiles');
        $this->addSql('ALTER TABLE contributor_profiles DROP company_id');
        $this->addSql('ALTER TABLE cookie_consents RENAME INDEX idx_cookie_consent_company TO idx_cookieconsent_company');
        $this->addSql('ALTER TABLE dim_contributor DROP FOREIGN KEY `fk_dim_contributor_company`');
        $this->addSql('ALTER TABLE dim_contributor ADD CONSTRAINT FK_8BC20A2C979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE dim_contributor RENAME INDEX idx_dim_contributor_company TO IDX_8BC20A2C979B1AD6');
        $this->addSql('ALTER TABLE dim_profile DROP FOREIGN KEY `fk_dim_profile_company`');
        $this->addSql('ALTER TABLE dim_profile ADD CONSTRAINT FK_9B698904979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE dim_profile RENAME INDEX idx_dim_profile_company TO IDX_9B698904979B1AD6');
        $this->addSql('ALTER TABLE dim_project_type DROP FOREIGN KEY `fk_dim_project_type_company`');
        $this->addSql('ALTER TABLE dim_project_type ADD CONSTRAINT FK_EEC09CB1979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE dim_project_type RENAME INDEX idx_dim_project_type_company TO IDX_EEC09CB1979B1AD6');
        $this->addSql('ALTER TABLE dim_time DROP FOREIGN KEY `fk_dim_time_company`');
        $this->addSql('ALTER TABLE dim_time ADD CONSTRAINT FK_6F547BD9979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE dim_time RENAME INDEX idx_dim_time_company TO IDX_6F547BD9979B1AD6');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT 35 NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT 100 NOT NULL');
        $this->addSql('ALTER TABLE employment_period_profiles DROP FOREIGN KEY `fk_employment_period_profile_company`');
        $this->addSql('DROP INDEX idx_employment_period_profile_company ON employment_period_profiles');
        $this->addSql('ALTER TABLE employment_period_profiles DROP company_id');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX idx_expense_report_company TO IDX_9C04EC7F979B1AD6');
        $this->addSql('ALTER TABLE fact_forecast RENAME INDEX idx_fact_forecast_company TO idx_factforecast_company');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY `fk_fact_project_metrics_company`');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT FK_27991A94979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE fact_project_metrics RENAME INDEX idx_fact_project_metrics_company TO IDX_27991A94979B1AD6');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY `fk_fact_staffing_metrics_company`');
        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT FK_F58C0D48979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE fact_staffing_metrics RENAME INDEX idx_fact_staffing_metrics_company TO IDX_F58C0D48979B1AD6');
        $this->addSql('DROP INDEX invoice_number_company_unique ON invoices');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2F2F952DA68207 ON invoices (invoice_number)');
        $this->addSql('ALTER TABLE invoices RENAME INDEX idx_invoice_company TO IDX_6A2F2F95979B1AD6');

        // Only rename index if table exists
        if ($leadCapturesExists) {
            $this->addSql('ALTER TABLE lead_captures RENAME INDEX idx_lead_capture_company TO idx_leadcapture_company');
        }

        $this->addSql('ALTER TABLE notification_preferences RENAME INDEX idx_notification_preference_company TO idx_notificationpreference_company');
        $this->addSql('DROP INDEX setting_key_company_unique ON notification_settings');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B05598605FA1E697 ON notification_settings (setting_key)');
        $this->addSql('ALTER TABLE notification_settings RENAME INDEX idx_notification_setting_company TO idx_notificationsetting_company');
        $this->addSql('ALTER TABLE nps_surveys RENAME INDEX idx_nps_survey_company TO idx_npssurvey_company');
        $this->addSql('ALTER TABLE onboarding_tasks RENAME INDEX idx_onboarding_task_company TO idx_onboardingtask_company');
        $this->addSql('ALTER TABLE onboarding_templates RENAME INDEX idx_onboarding_template_company TO idx_onboardingtemplate_company');
        $this->addSql('ALTER TABLE order_lines RENAME INDEX idx_order_line_company TO IDX_CC9FF86B979B1AD6');
        $this->addSql('ALTER TABLE order_payment_schedules RENAME INDEX idx_order_payment_schedule_company TO idx_orderpaymentschedule_company');
        $this->addSql('ALTER TABLE order_sections RENAME INDEX idx_order_section_company TO IDX_CA6EA129979B1AD6');
        $this->addSql('ALTER TABLE order_tasks RENAME INDEX idx_order_task_company TO idx_ordertask_company');
        $this->addSql('DROP INDEX order_number_company_unique ON orders');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE551F0F81 ON orders (order_number)');
        $this->addSql('ALTER TABLE orders RENAME INDEX idx_order_company TO IDX_E52FFDEE979B1AD6');
        $this->addSql('ALTER TABLE performance_reviews RENAME INDEX idx_performance_review_company TO idx_performancereview_company');
        $this->addSql('DROP INDEX profile_name_company_unique ON profiles');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8B3085305E237E06 ON profiles (name)');
        $this->addSql('ALTER TABLE project_events RENAME INDEX idx_project_event_company TO idx_projectevent_company');
        $this->addSql('ALTER TABLE project_health_score RENAME INDEX idx_project_health_score_company TO idx_projecthealthscore_company');
        $this->addSql('ALTER TABLE project_sub_tasks RENAME INDEX idx_project_sub_task_company TO idx_projectsubtask_company');
        $this->addSql('ALTER TABLE project_tasks RENAME INDEX idx_project_task_company TO idx_projecttask_company');
        $this->addSql('ALTER TABLE project_technology_versions RENAME INDEX idx_project_technology_version_company TO idx_projecttechnology_company');
        $this->addSql('ALTER TABLE projects RENAME INDEX idx_project_company TO IDX_5C93B3A4979B1AD6');
        $this->addSql('ALTER TABLE project_technologies DROP FOREIGN KEY `fk_project_technology_company`');
        $this->addSql('DROP INDEX idx_project_technology_company ON project_technologies');
        $this->addSql('ALTER TABLE project_technologies DROP company_id');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_running_timer_company TO idx_runningtimer_company');
        $this->addSql('ALTER TABLE saas_distribution_providers RENAME INDEX idx_saas_distribution_provider_company TO idx_provider_company');
        $this->addSql('ALTER TABLE saas_providers RENAME INDEX idx_saas_provider_company TO idx_saasprovider_company');
        $this->addSql('ALTER TABLE saas_services RENAME INDEX idx_saas_service_company TO idx_saasservice_company');
        $this->addSql('ALTER TABLE saas_subscriptions RENAME INDEX idx_saas_subscription_company TO idx_saassubscription_company');
        $this->addSql('ALTER TABLE saas_subscriptions_v2 RENAME INDEX idx_saas_subscription_v2_company TO idx_subscription_company');
        $this->addSql('ALTER TABLE saas_vendors RENAME INDEX idx_saas_vendor_company TO idx_vendor_company');
        $this->addSql('ALTER TABLE scheduler_entries RENAME INDEX idx_scheduler_entry_company TO idx_schedulerentry_company');
        $this->addSql('ALTER TABLE service_categories RENAME INDEX idx_service_category_company TO idx_servicecategory_company');
        $this->addSql('DROP INDEX skill_name_company_unique ON skills');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D53116705E237E06 ON skills (name)');
        $this->addSql('ALTER TABLE timesheets RENAME INDEX idx_timesheet_company TO IDX_9AC77D2E979B1AD6');
        $this->addSql('DROP INDEX email_company_unique ON users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE xp_history RENAME INDEX idx_xp_history_company TO idx_xphistory_company');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        // Check if table exists before modifying (table may not exist in all environments)
        $leadCapturesExists = $schema->hasTable('lead_captures');

        $this->addSql('ALTER TABLE account_deletion_requests RENAME INDEX idx_accountdeletionrequest_company TO idx_account_deletion_request_company');
        $this->addSql('ALTER TABLE billing_markers RENAME INDEX idx_billingmarker_company TO idx_billing_marker_company');
        $this->addSql('CREATE UNIQUE INDEX company_settings_company_unique ON company_settings (company_id)');
        $this->addSql('ALTER TABLE company_settings RENAME INDEX idx_companysettings_company TO idx_company_settings_company');
        $this->addSql('ALTER TABLE contributor_profiles ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE contributor_profiles ADD CONSTRAINT `fk_contributor_profile_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_contributor_profile_company ON contributor_profiles (company_id)');
        $this->addSql('ALTER TABLE contributor_progress RENAME INDEX idx_contributorprogress_company TO idx_contributor_progress_company');
        $this->addSql('ALTER TABLE contributor_satisfactions RENAME INDEX idx_contributorsatisfaction_company TO idx_contributor_satisfaction_company');
        $this->addSql('ALTER TABLE contributor_skills RENAME INDEX idx_contributorskill_company TO idx_contributor_skill_company');
        $this->addSql('ALTER TABLE cookie_consents RENAME INDEX idx_cookieconsent_company TO idx_cookie_consent_company');
        $this->addSql('ALTER TABLE dim_contributor DROP FOREIGN KEY FK_8BC20A2C979B1AD6');
        $this->addSql('ALTER TABLE dim_contributor ADD CONSTRAINT `fk_dim_contributor_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dim_contributor RENAME INDEX idx_8bc20a2c979b1ad6 TO idx_dim_contributor_company');
        $this->addSql('ALTER TABLE dim_profile DROP FOREIGN KEY FK_9B698904979B1AD6');
        $this->addSql('ALTER TABLE dim_profile ADD CONSTRAINT `fk_dim_profile_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dim_profile RENAME INDEX idx_9b698904979b1ad6 TO idx_dim_profile_company');
        $this->addSql('ALTER TABLE dim_project_type DROP FOREIGN KEY FK_EEC09CB1979B1AD6');
        $this->addSql('ALTER TABLE dim_project_type ADD CONSTRAINT `fk_dim_project_type_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dim_project_type RENAME INDEX idx_eec09cb1979b1ad6 TO idx_dim_project_type_company');
        $this->addSql('ALTER TABLE dim_time DROP FOREIGN KEY FK_6F547BD9979B1AD6');
        $this->addSql('ALTER TABLE dim_time ADD CONSTRAINT `fk_dim_time_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dim_time RENAME INDEX idx_6f547bd9979b1ad6 TO idx_dim_time_company');
        $this->addSql('ALTER TABLE employment_periods CHANGE weekly_hours weekly_hours NUMERIC(5, 2) DEFAULT \'35.00\' NOT NULL, CHANGE work_time_percentage work_time_percentage NUMERIC(5, 2) DEFAULT \'100.00\' NOT NULL');
        $this->addSql('ALTER TABLE employment_period_profiles ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE employment_period_profiles ADD CONSTRAINT `fk_employment_period_profile_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_employment_period_profile_company ON employment_period_profiles (company_id)');
        $this->addSql('ALTER TABLE expense_reports RENAME INDEX idx_9c04ec7f979b1ad6 TO idx_expense_report_company');
        $this->addSql('ALTER TABLE fact_forecast RENAME INDEX idx_factforecast_company TO idx_fact_forecast_company');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94979B1AD6');
        $this->addSql('ALTER TABLE fact_project_metrics ADD CONSTRAINT `fk_fact_project_metrics_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fact_project_metrics RENAME INDEX idx_27991a94979b1ad6 TO idx_fact_project_metrics_company');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D48979B1AD6');
        $this->addSql('ALTER TABLE fact_staffing_metrics ADD CONSTRAINT `fk_fact_staffing_metrics_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fact_staffing_metrics RENAME INDEX idx_f58c0d48979b1ad6 TO idx_fact_staffing_metrics_company');
        $this->addSql('DROP INDEX UNIQ_6A2F2F952DA68207 ON invoices');
        $this->addSql('CREATE UNIQUE INDEX invoice_number_company_unique ON invoices (invoice_number, company_id)');
        $this->addSql('ALTER TABLE invoices RENAME INDEX idx_6a2f2f95979b1ad6 TO idx_invoice_company');

        // Only rename index if table exists
        if ($leadCapturesExists) {
            $this->addSql('ALTER TABLE lead_captures RENAME INDEX idx_leadcapture_company TO idx_lead_capture_company');
        }

        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('ALTER TABLE notification_preferences RENAME INDEX idx_notificationpreference_company TO idx_notification_preference_company');
        $this->addSql('DROP INDEX UNIQ_B05598605FA1E697 ON notification_settings');
        $this->addSql('CREATE UNIQUE INDEX setting_key_company_unique ON notification_settings (setting_key, company_id)');
        $this->addSql('ALTER TABLE notification_settings RENAME INDEX idx_notificationsetting_company TO idx_notification_setting_company');
        $this->addSql('ALTER TABLE nps_surveys RENAME INDEX idx_npssurvey_company TO idx_nps_survey_company');
        $this->addSql('ALTER TABLE onboarding_tasks RENAME INDEX idx_onboardingtask_company TO idx_onboarding_task_company');
        $this->addSql('ALTER TABLE onboarding_templates RENAME INDEX idx_onboardingtemplate_company TO idx_onboarding_template_company');
        $this->addSql('DROP INDEX UNIQ_E52FFDEE551F0F81 ON orders');
        $this->addSql('CREATE UNIQUE INDEX order_number_company_unique ON orders (order_number, company_id)');
        $this->addSql('ALTER TABLE orders RENAME INDEX idx_e52ffdee979b1ad6 TO idx_order_company');
        $this->addSql('ALTER TABLE order_lines RENAME INDEX idx_cc9ff86b979b1ad6 TO idx_order_line_company');
        $this->addSql('ALTER TABLE order_payment_schedules RENAME INDEX idx_orderpaymentschedule_company TO idx_order_payment_schedule_company');
        $this->addSql('ALTER TABLE order_sections RENAME INDEX idx_ca6ea129979b1ad6 TO idx_order_section_company');
        $this->addSql('ALTER TABLE order_tasks RENAME INDEX idx_ordertask_company TO idx_order_task_company');
        $this->addSql('ALTER TABLE performance_reviews RENAME INDEX idx_performancereview_company TO idx_performance_review_company');
        $this->addSql('DROP INDEX UNIQ_8B3085305E237E06 ON profiles');
        $this->addSql('CREATE UNIQUE INDEX profile_name_company_unique ON profiles (name, company_id)');
        $this->addSql('ALTER TABLE projects RENAME INDEX idx_5c93b3a4979b1ad6 TO idx_project_company');
        $this->addSql('ALTER TABLE project_events RENAME INDEX idx_projectevent_company TO idx_project_event_company');
        $this->addSql('ALTER TABLE project_health_score RENAME INDEX idx_projecthealthscore_company TO idx_project_health_score_company');
        $this->addSql('ALTER TABLE project_sub_tasks RENAME INDEX idx_projectsubtask_company TO idx_project_sub_task_company');
        $this->addSql('ALTER TABLE project_tasks RENAME INDEX idx_projecttask_company TO idx_project_task_company');
        $this->addSql('ALTER TABLE project_technologies ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE project_technologies ADD CONSTRAINT `fk_project_technology_company` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_project_technology_company ON project_technologies (company_id)');
        $this->addSql('ALTER TABLE project_technology_versions RENAME INDEX idx_projecttechnology_company TO idx_project_technology_version_company');
        $this->addSql('ALTER TABLE running_timers RENAME INDEX idx_runningtimer_company TO idx_running_timer_company');
        $this->addSql('ALTER TABLE saas_distribution_providers RENAME INDEX idx_provider_company TO idx_saas_distribution_provider_company');
        $this->addSql('ALTER TABLE saas_providers RENAME INDEX idx_saasprovider_company TO idx_saas_provider_company');
        $this->addSql('ALTER TABLE saas_services RENAME INDEX idx_saasservice_company TO idx_saas_service_company');
        $this->addSql('ALTER TABLE saas_subscriptions RENAME INDEX idx_saassubscription_company TO idx_saas_subscription_company');
        $this->addSql('ALTER TABLE saas_subscriptions_v2 RENAME INDEX idx_subscription_company TO idx_saas_subscription_v2_company');
        $this->addSql('ALTER TABLE saas_vendors RENAME INDEX idx_vendor_company TO idx_saas_vendor_company');
        $this->addSql('ALTER TABLE scheduler_entries RENAME INDEX idx_schedulerentry_company TO idx_scheduler_entry_company');
        $this->addSql('ALTER TABLE service_categories RENAME INDEX idx_servicecategory_company TO idx_service_category_company');
        $this->addSql('DROP INDEX UNIQ_D53116705E237E06 ON skills');
        $this->addSql('CREATE UNIQUE INDEX skill_name_company_unique ON skills (name, company_id)');
        $this->addSql('ALTER TABLE timesheets RENAME INDEX idx_9ac77d2e979b1ad6 TO idx_timesheet_company');
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74 ON users');
        $this->addSql('CREATE UNIQUE INDEX email_company_unique ON users (email, company_id)');
        $this->addSql('ALTER TABLE xp_history RENAME INDEX idx_xphistory_company TO idx_xp_history_company');
    }
}
