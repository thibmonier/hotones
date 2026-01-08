<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108113040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE account_deletion_requests (
              id INT AUTO_INCREMENT NOT NULL,
              status VARCHAR(20) NOT NULL,
              confirmation_token VARCHAR(64) NOT NULL,
              requested_at DATETIME NOT NULL,
              confirmed_at DATETIME DEFAULT NULL,
              scheduled_deletion_at DATETIME DEFAULT NULL,
              cancelled_at DATETIME DEFAULT NULL,
              completed_at DATETIME DEFAULT NULL,
              reason LONGTEXT DEFAULT NULL,
              ip_address VARCHAR(45) DEFAULT NULL,
              company_id INT NOT NULL,
              user_id INT NOT NULL,
              UNIQUE INDEX UNIQ_748FBDF6C05FB297 (confirmation_token),
              INDEX idx_deletion_request_user (user_id),
              INDEX idx_deletion_request_status (status),
              INDEX idx_deletion_scheduled (scheduled_deletion_at),
              INDEX idx_accountdeletionrequest_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE achievements (
              id INT AUTO_INCREMENT NOT NULL,
              unlocked_at DATETIME NOT NULL,
              notified TINYINT NOT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              badge_id INT NOT NULL,
              INDEX IDX_D1227EFE7A19A357 (contributor_id),
              INDEX IDX_D1227EFEF7A2C2FC (badge_id),
              INDEX idx_achievement_company (company_id),
              UNIQUE INDEX unique_contributor_badge (contributor_id, badge_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE badges (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(100) NOT NULL,
              description LONGTEXT NOT NULL,
              icon VARCHAR(50) NOT NULL,
              category VARCHAR(50) NOT NULL,
              xp_reward INT NOT NULL,
              criteria JSON DEFAULT NULL,
              active TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              INDEX idx_badge_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE billing_markers (
              id INT AUTO_INCREMENT NOT NULL,
              year SMALLINT DEFAULT NULL,
              month SMALLINT DEFAULT NULL,
              is_issued TINYINT DEFAULT 0 NOT NULL,
              issued_at DATE DEFAULT NULL,
              paid_at DATE DEFAULT NULL,
              comment LONGTEXT DEFAULT NULL,
              company_id INT NOT NULL,
              schedule_id INT DEFAULT NULL,
              order_id INT DEFAULT NULL,
              INDEX IDX_2AA754E78D9F6D38 (order_id),
              INDEX idx_billingmarker_company (company_id),
              UNIQUE INDEX uniq_marker_schedule (schedule_id),
              UNIQUE INDEX uniq_marker_regie_period (order_id, year, month),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE business_units (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              annual_revenue_target DECIMAL(12, 2) DEFAULT NULL,
              annual_margin_target DECIMAL(5, 2) DEFAULT NULL,
              headcount_target INT DEFAULT NULL,
              cost_center VARCHAR(100) DEFAULT NULL,
              active TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              parent_id INT DEFAULT NULL,
              manager_id INT DEFAULT NULL,
              INDEX IDX_975193F6783E3463 (manager_id),
              INDEX idx_bu_company (company_id),
              INDEX idx_bu_parent (parent_id),
              INDEX idx_bu_active (active),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE client_contacts (
              id INT AUTO_INCREMENT NOT NULL,
              last_name VARCHAR(100) NOT NULL,
              first_name VARCHAR(100) NOT NULL,
              email VARCHAR(180) DEFAULT NULL,
              phone VARCHAR(50) DEFAULT NULL,
              mobile_phone VARCHAR(50) DEFAULT NULL,
              position_title VARCHAR(120) DEFAULT NULL,
              active TINYINT NOT NULL,
              company_id INT NOT NULL,
              client_id INT NOT NULL,
              INDEX IDX_1DA625B619EB6921 (client_id),
              INDEX idx_client_contact_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clients (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(180) NOT NULL,
              logo_path VARCHAR(255) DEFAULT NULL,
              website VARCHAR(255) DEFAULT NULL,
              description LONGTEXT DEFAULT NULL,
              service_level VARCHAR(20) DEFAULT NULL,
              service_level_mode VARCHAR(10) DEFAULT 'auto' NOT NULL,
              company_id INT NOT NULL,
              INDEX idx_client_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE companies (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              slug VARCHAR(100) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              status VARCHAR(50) NOT NULL,
              subscription_tier VARCHAR(50) NOT NULL,
              max_users INT DEFAULT NULL,
              max_projects INT DEFAULT NULL,
              max_storage_mb INT DEFAULT NULL,
              billing_start_date DATE NOT NULL,
              billing_day_of_month INT NOT NULL,
              currency VARCHAR(3) NOT NULL,
              settings JSON NOT NULL,
              enabled_features JSON NOT NULL,
              structure_cost_coefficient DECIMAL(10, 4) NOT NULL,
              employer_charges_coefficient DECIMAL(10, 4) NOT NULL,
              annual_paid_leave_days INT NOT NULL,
              annual_rtt_days INT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              suspended_at DATETIME DEFAULT NULL,
              trial_ends_at DATETIME DEFAULT NULL,
              owner_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_8244AA3A989D9B62 (slug),
              INDEX IDX_8244AA3A7E3C61F9 (owner_id),
              INDEX idx_company_slug (slug),
              INDEX idx_company_status (status),
              INDEX idx_company_subscription_tier (subscription_tier),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE company_settings (
              id INT AUTO_INCREMENT NOT NULL,
              structure_cost_coefficient DECIMAL(10, 4) NOT NULL,
              employer_charges_coefficient DECIMAL(10, 4) NOT NULL,
              annual_paid_leave_days INT NOT NULL,
              annual_rtt_days INT NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              INDEX idx_companysettings_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contributor_progress (
              id INT AUTO_INCREMENT NOT NULL,
              total_xp INT NOT NULL,
              level INT NOT NULL,
              title VARCHAR(50) DEFAULT NULL,
              current_level_xp INT NOT NULL,
              next_level_xp INT NOT NULL,
              last_xp_gained_at DATETIME NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              UNIQUE INDEX UNIQ_14C777707A19A357 (contributor_id),
              INDEX idx_contributorprogress_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contributor_satisfactions (
              id INT AUTO_INCREMENT NOT NULL,
              year INT NOT NULL,
              month INT NOT NULL,
              overall_score INT NOT NULL,
              projects_score INT DEFAULT NULL,
              team_score INT DEFAULT NULL,
              work_environment_score INT DEFAULT NULL,
              work_life_balance_score INT DEFAULT NULL,
              comment LONGTEXT DEFAULT NULL,
              positive_points LONGTEXT DEFAULT NULL,
              improvement_points LONGTEXT DEFAULT NULL,
              submitted_at DATETIME NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              INDEX IDX_77E270717A19A357 (contributor_id),
              INDEX idx_contributorsatisfaction_company (company_id),
              UNIQUE INDEX unique_contributor_period (contributor_id, year, month),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contributor_skills (
              id INT AUTO_INCREMENT NOT NULL,
              self_assessment_level INT NOT NULL,
              manager_assessment_level INT DEFAULT NULL,
              date_acquired DATE DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              skill_id INT NOT NULL,
              INDEX IDX_A0CA02C87A19A357 (contributor_id),
              INDEX IDX_A0CA02C85585C142 (skill_id),
              INDEX idx_contributorskill_company (company_id),
              UNIQUE INDEX contributor_skill_unique (contributor_id, skill_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contributors (
              id INT AUTO_INCREMENT NOT NULL,
              first_name VARCHAR(100) NOT NULL,
              last_name VARCHAR(100) NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              phone_personal VARCHAR(20) DEFAULT NULL,
              phone_professional VARCHAR(20) DEFAULT NULL,
              birth_date DATE DEFAULT NULL,
              gender VARCHAR(10) DEFAULT NULL,
              address LONGTEXT DEFAULT NULL,
              avatar_filename VARCHAR(255) DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              cjm DECIMAL(10, 2) DEFAULT NULL,
              tjm DECIMAL(10, 2) DEFAULT NULL,
              active TINYINT NOT NULL,
              company_id INT NOT NULL,
              user_id INT DEFAULT NULL,
              manager_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_72D26262A76ED395 (user_id),
              INDEX IDX_72D26262783E3463 (manager_id),
              INDEX idx_contributor_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contributor_profiles (
              contributor_id INT NOT NULL,
              profile_id INT NOT NULL,
              INDEX IDX_BDF600067A19A357 (contributor_id),
              INDEX IDX_BDF60006CCFA12B8 (profile_id),
              PRIMARY KEY (contributor_id, profile_id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE cookie_consents (
              id INT AUTO_INCREMENT NOT NULL,
              essential TINYINT NOT NULL,
              functional TINYINT NOT NULL,
              analytics TINYINT NOT NULL,
              version VARCHAR(10) NOT NULL,
              ip_address VARCHAR(45) DEFAULT NULL,
              user_agent LONGTEXT DEFAULT NULL,
              created_at DATETIME NOT NULL,
              expires_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              user_id INT DEFAULT NULL,
              INDEX idx_cookie_consent_user (user_id),
              INDEX idx_cookie_consent_created (created_at),
              INDEX idx_cookie_consent_expires (expires_at),
              INDEX idx_cookieconsent_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dim_contributor (
              id INT AUTO_INCREMENT NOT NULL,
              name_value VARCHAR(180) NOT NULL,
              role_value VARCHAR(50) NOT NULL,
              is_active TINYINT NOT NULL,
              composite_key VARCHAR(250) NOT NULL,
              company_id INT NOT NULL,
              user_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_8BC20A2C13775659 (composite_key),
              INDEX IDX_8BC20A2C979B1AD6 (company_id),
              INDEX IDX_8BC20A2CA76ED395 (user_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dim_profile (
              id INT AUTO_INCREMENT NOT NULL,
              name_value VARCHAR(100) NOT NULL,
              is_productive TINYINT NOT NULL,
              is_active TINYINT NOT NULL,
              composite_key VARCHAR(150) NOT NULL,
              company_id INT NOT NULL,
              profile_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_9B69890413775659 (composite_key),
              INDEX IDX_9B698904979B1AD6 (company_id),
              INDEX IDX_9B698904CCFA12B8 (profile_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dim_project_type (
              id INT AUTO_INCREMENT NOT NULL,
              project_type VARCHAR(20) NOT NULL,
              service_category VARCHAR(50) DEFAULT NULL,
              status_value VARCHAR(20) NOT NULL,
              is_internal TINYINT NOT NULL,
              composite_key VARCHAR(150) NOT NULL,
              company_id INT NOT NULL,
              UNIQUE INDEX UNIQ_EEC09CB113775659 (composite_key),
              INDEX IDX_EEC09CB1979B1AD6 (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dim_time (
              id INT AUTO_INCREMENT NOT NULL,
              date_value DATE NOT NULL,
              year_value INT NOT NULL,
              quarter_value INT NOT NULL,
              month_value INT NOT NULL,
              period_year_month VARCHAR(20) NOT NULL,
              period_year_quarter VARCHAR(20) NOT NULL,
              month_name VARCHAR(50) NOT NULL,
              quarter_name VARCHAR(50) NOT NULL,
              company_id INT NOT NULL,
              UNIQUE INDEX UNIQ_6F547BD9A787B0B8 (date_value),
              INDEX IDX_6F547BD9979B1AD6 (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employment_periods (
              id INT AUTO_INCREMENT NOT NULL,
              salary DECIMAL(10, 2) DEFAULT NULL,
              cjm DECIMAL(10, 2) DEFAULT NULL,
              tjm DECIMAL(10, 2) DEFAULT NULL,
              weekly_hours DECIMAL(5, 2) DEFAULT 35 NOT NULL,
              work_time_percentage DECIMAL(5, 2) DEFAULT 100 NOT NULL,
              start_date DATE NOT NULL,
              end_date DATE DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              INDEX IDX_B996D77B7A19A357 (contributor_id),
              INDEX idx_employment_period_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employment_period_profiles (
              employment_period_id INT NOT NULL,
              profile_id INT NOT NULL,
              INDEX IDX_A643DBB15A128608 (employment_period_id),
              INDEX IDX_A643DBB1CCFA12B8 (profile_id),
              PRIMARY KEY (
                employment_period_id, profile_id
              )
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE expense_reports (
              id INT AUTO_INCREMENT NOT NULL,
              expense_date DATE NOT NULL,
              category VARCHAR(50) NOT NULL,
              description LONGTEXT NOT NULL,
              amount_ht DECIMAL(10, 2) NOT NULL,
              vat_rate DECIMAL(5, 2) NOT NULL,
              amount_ttc DECIMAL(10, 2) NOT NULL,
              status VARCHAR(20) NOT NULL,
              file_path VARCHAR(255) DEFAULT NULL,
              validated_at DATETIME DEFAULT NULL,
              validation_comment LONGTEXT DEFAULT NULL,
              paid_at DATETIME DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              project_id INT DEFAULT NULL,
              order_id INT DEFAULT NULL,
              validator_id INT DEFAULT NULL,
              INDEX IDX_9C04EC7F979B1AD6 (company_id),
              INDEX IDX_9C04EC7F7A19A357 (contributor_id),
              INDEX IDX_9C04EC7F166D1F9C (project_id),
              INDEX IDX_9C04EC7F8D9F6D38 (order_id),
              INDEX IDX_9C04EC7FB0644AEC (validator_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE fact_forecast (
              id INT AUTO_INCREMENT NOT NULL,
              period_start DATE NOT NULL,
              period_end DATE NOT NULL,
              scenario VARCHAR(20) NOT NULL,
              predicted_revenue DECIMAL(10, 2) NOT NULL,
              confidence_min DECIMAL(10, 2) DEFAULT NULL,
              confidence_max DECIMAL(10, 2) DEFAULT NULL,
              actual_revenue DECIMAL(10, 2) DEFAULT NULL,
              accuracy DECIMAL(5, 2) DEFAULT NULL,
              metadata JSON DEFAULT NULL,
              created_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              INDEX idx_period (period_start, period_end),
              INDEX idx_scenario (scenario),
              INDEX idx_created_at (created_at),
              INDEX idx_factforecast_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE fact_project_metrics (
              id INT AUTO_INCREMENT NOT NULL,
              project_count INT NOT NULL,
              active_project_count INT NOT NULL,
              completed_project_count INT NOT NULL,
              order_count INT NOT NULL,
              pending_order_count INT NOT NULL,
              won_order_count INT NOT NULL,
              signed_order_count INT NOT NULL,
              lost_order_count INT NOT NULL,
              contributor_count INT NOT NULL,
              total_revenue DECIMAL(15, 2) NOT NULL,
              total_costs DECIMAL(15, 2) NOT NULL,
              gross_margin DECIMAL(15, 2) NOT NULL,
              margin_percentage DECIMAL(5, 2) NOT NULL,
              pending_revenue DECIMAL(15, 2) NOT NULL,
              average_order_value DECIMAL(15, 2) NOT NULL,
              total_sold_days DECIMAL(10, 2) NOT NULL,
              total_worked_days DECIMAL(10, 2) NOT NULL,
              utilization_rate DECIMAL(5, 2) NOT NULL,
              calculated_at DATETIME NOT NULL,
              granularity VARCHAR(50) NOT NULL,
              company_id INT NOT NULL,
              dim_time_id INT NOT NULL,
              dim_project_type_id INT NOT NULL,
              dim_project_manager_id INT DEFAULT NULL,
              dim_sales_person_id INT DEFAULT NULL,
              dim_project_director_id INT DEFAULT NULL,
              project_id INT DEFAULT NULL,
              order_id INT DEFAULT NULL,
              INDEX IDX_27991A94979B1AD6 (company_id),
              INDEX IDX_27991A9444D4FE30 (dim_time_id),
              INDEX IDX_27991A94E44D565F (dim_project_type_id),
              INDEX IDX_27991A94EC5B4665 (dim_project_manager_id),
              INDEX IDX_27991A94AA2A35A7 (dim_sales_person_id),
              INDEX IDX_27991A9460687321 (dim_project_director_id),
              INDEX IDX_27991A94166D1F9C (project_id),
              INDEX IDX_27991A948D9F6D38 (order_id),
              UNIQUE INDEX unique_fact_metrics (
                dim_time_id, dim_project_type_id,
                dim_project_manager_id, dim_sales_person_id,
                dim_project_director_id, granularity,
                project_id, order_id
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE fact_staffing_metrics (
              id INT AUTO_INCREMENT NOT NULL,
              available_days DECIMAL(10, 2) NOT NULL,
              worked_days DECIMAL(10, 2) NOT NULL,
              staffed_days DECIMAL(10, 2) NOT NULL,
              vacation_days DECIMAL(10, 2) NOT NULL,
              planned_days DECIMAL(10, 2) NOT NULL,
              staffing_rate DECIMAL(5, 2) NOT NULL,
              tace DECIMAL(5, 2) NOT NULL,
              calculated_at DATETIME NOT NULL,
              granularity VARCHAR(50) NOT NULL,
              contributor_count INT NOT NULL,
              company_id INT NOT NULL,
              dim_time_id INT NOT NULL,
              dim_profile_id INT DEFAULT NULL,
              contributor_id INT DEFAULT NULL,
              INDEX IDX_F58C0D48979B1AD6 (company_id),
              INDEX IDX_F58C0D4844D4FE30 (dim_time_id),
              INDEX IDX_F58C0D48BF679789 (dim_profile_id),
              INDEX IDX_F58C0D487A19A357 (contributor_id),
              UNIQUE INDEX unique_staffing_metrics (
                dim_time_id, dim_profile_id, contributor_id,
                granularity
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE invoice_lines (
              id INT AUTO_INCREMENT NOT NULL,
              description LONGTEXT NOT NULL,
              quantity DECIMAL(10, 2) NOT NULL,
              unit VARCHAR(50) NOT NULL,
              unit_price_ht DECIMAL(12, 2) NOT NULL,
              total_ht DECIMAL(12, 2) NOT NULL,
              tva_rate DECIMAL(5, 2) NOT NULL,
              tva_amount DECIMAL(12, 2) NOT NULL,
              total_ttc DECIMAL(12, 2) NOT NULL,
              display_order INT NOT NULL,
              company_id INT NOT NULL,
              invoice_id INT NOT NULL,
              INDEX IDX_72DBDC232989F1FD (invoice_id),
              INDEX idx_invoice_line_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE invoices (
              id INT AUTO_INCREMENT NOT NULL,
              invoice_number VARCHAR(50) NOT NULL,
              status VARCHAR(20) NOT NULL,
              issued_at DATE NOT NULL,
              due_date DATE NOT NULL,
              paid_at DATE DEFAULT NULL,
              amount_ht DECIMAL(12, 2) NOT NULL,
              amount_tva DECIMAL(12, 2) NOT NULL,
              tva_rate DECIMAL(5, 2) NOT NULL,
              amount_ttc DECIMAL(12, 2) NOT NULL,
              internal_notes LONGTEXT DEFAULT NULL,
              payment_terms LONGTEXT DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              order_id INT DEFAULT NULL,
              project_id INT DEFAULT NULL,
              client_id INT NOT NULL,
              payment_schedule_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_6A2F2F952DA68207 (invoice_number),
              INDEX IDX_6A2F2F95979B1AD6 (company_id),
              INDEX IDX_6A2F2F958D9F6D38 (order_id),
              INDEX IDX_6A2F2F95166D1F9C (project_id),
              INDEX IDX_6A2F2F9519EB6921 (client_id),
              INDEX IDX_6A2F2F955287120F (payment_schedule_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lead_captures (
              id INT AUTO_INCREMENT NOT NULL,
              email VARCHAR(255) NOT NULL,
              first_name VARCHAR(100) NOT NULL,
              last_name VARCHAR(100) NOT NULL,
              company VARCHAR(255) DEFAULT NULL,
              phone VARCHAR(50) DEFAULT NULL,
              source VARCHAR(50) NOT NULL,
              content_type VARCHAR(100) NOT NULL,
              downloaded_at DATETIME DEFAULT NULL,
              download_count INT DEFAULT 0 NOT NULL,
              marketing_consent TINYINT DEFAULT 0 NOT NULL,
              internal_notes LONGTEXT DEFAULT NULL,
              status VARCHAR(50) DEFAULT 'new' NOT NULL,
              nurturing_day1_sent_at DATETIME DEFAULT NULL,
              nurturing_day3_sent_at DATETIME DEFAULT NULL,
              nurturing_day7_sent_at DATETIME DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              INDEX idx_lead_email (email),
              INDEX idx_lead_source (source),
              INDEX idx_lead_created_at (created_at),
              INDEX idx_leadcapture_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification_preferences (
              id INT AUTO_INCREMENT NOT NULL,
              event_type VARCHAR(255) NOT NULL,
              in_app TINYINT NOT NULL,
              email TINYINT NOT NULL,
              webhook TINYINT NOT NULL,
              company_id INT NOT NULL,
              user_id INT NOT NULL,
              INDEX IDX_3CAA95B4A76ED395 (user_id),
              INDEX idx_notificationpreference_company (company_id),
              UNIQUE INDEX user_event_unique (user_id, event_type),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification_settings (
              id INT AUTO_INCREMENT NOT NULL,
              setting_key VARCHAR(100) NOT NULL,
              setting_value JSON NOT NULL,
              company_id INT NOT NULL,
              UNIQUE INDEX UNIQ_B05598605FA1E697 (setting_key),
              INDEX idx_notificationsetting_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notifications (
              id INT AUTO_INCREMENT NOT NULL,
              type VARCHAR(255) NOT NULL,
              title VARCHAR(255) NOT NULL,
              message LONGTEXT NOT NULL,
              data JSON DEFAULT NULL,
              entity_type VARCHAR(100) DEFAULT NULL,
              entity_id INT DEFAULT NULL,
              read_at DATETIME DEFAULT NULL,
              created_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              recipient_id INT NOT NULL,
              INDEX IDX_6000B0D3E92F8F78 (recipient_id),
              INDEX idx_notification_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE nps_surveys (
              id INT AUTO_INCREMENT NOT NULL,
              token VARCHAR(64) NOT NULL,
              sent_at DATETIME NOT NULL,
              responded_at DATETIME DEFAULT NULL,
              score INT DEFAULT NULL,
              comment LONGTEXT DEFAULT NULL,
              created_at DATETIME DEFAULT NULL,
              updated_at DATETIME DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              recipient_email VARCHAR(255) NOT NULL,
              recipient_name VARCHAR(255) DEFAULT NULL,
              expires_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              project_id INT NOT NULL,
              UNIQUE INDEX UNIQ_E88066E95F37A13B (token),
              INDEX IDX_E88066E9166D1F9C (project_id),
              INDEX idx_npssurvey_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE onboarding_tasks (
              id INT AUTO_INCREMENT NOT NULL,
              title VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              order_num INT NOT NULL,
              assigned_to VARCHAR(50) NOT NULL,
              type VARCHAR(50) NOT NULL,
              days_after_start INT NOT NULL,
              due_date DATETIME DEFAULT NULL,
              status VARCHAR(50) NOT NULL,
              completed_at DATETIME DEFAULT NULL,
              comments LONGTEXT DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              template_id INT DEFAULT NULL,
              INDEX IDX_6DCA087B7A19A357 (contributor_id),
              INDEX IDX_6DCA087B5DA0FB8 (template_id),
              INDEX idx_onboarding_task_status (status),
              INDEX idx_onboarding_task_due_date (due_date),
              INDEX idx_onboardingtask_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE onboarding_templates (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              tasks JSON NOT NULL,
              active TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              profile_id INT DEFAULT NULL,
              INDEX IDX_A8917FF4CCFA12B8 (profile_id),
              INDEX idx_onboardingtemplate_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE order_lines (
              id INT AUTO_INCREMENT NOT NULL,
              description VARCHAR(255) NOT NULL,
              position INT NOT NULL,
              daily_rate DECIMAL(10, 2) DEFAULT NULL,
              days DECIMAL(8, 2) DEFAULT NULL,
              direct_amount DECIMAL(12, 2) DEFAULT NULL,
              attached_purchase_amount DECIMAL(12, 2) DEFAULT NULL,
              type VARCHAR(50) NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              company_id INT NOT NULL,
              section_id INT NOT NULL,
              profile_id INT DEFAULT NULL,
              INDEX IDX_CC9FF86B979B1AD6 (company_id),
              INDEX IDX_CC9FF86BD823E37A (section_id),
              INDEX IDX_CC9FF86BCCFA12B8 (profile_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE order_payment_schedules (
              id INT AUTO_INCREMENT NOT NULL,
              label VARCHAR(255) DEFAULT NULL,
              billing_date DATE NOT NULL,
              amount_type VARCHAR(20) NOT NULL,
              percent DECIMAL(5, 2) DEFAULT NULL,
              fixed_amount DECIMAL(12, 2) DEFAULT NULL,
              company_id INT NOT NULL,
              order_id INT NOT NULL,
              INDEX IDX_6671B9FD8D9F6D38 (order_id),
              INDEX idx_orderpaymentschedule_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE order_sections (
              id INT AUTO_INCREMENT NOT NULL,
              title VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              position INT NOT NULL,
              company_id INT NOT NULL,
              order_id INT NOT NULL,
              INDEX IDX_CA6EA129979B1AD6 (company_id),
              INDEX IDX_CA6EA1298D9F6D38 (order_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE order_tasks (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(180) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              sold_days DECIMAL(8, 2) NOT NULL,
              sold_daily_rate DECIMAL(10, 2) NOT NULL,
              total_amount DECIMAL(12, 2) NOT NULL,
              company_id INT NOT NULL,
              order_id INT NOT NULL,
              profile_id INT NOT NULL,
              INDEX IDX_D3C6116A8D9F6D38 (order_id),
              INDEX IDX_D3C6116ACCFA12B8 (profile_id),
              INDEX idx_ordertask_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE orders (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(180) DEFAULT NULL,
              description LONGTEXT DEFAULT NULL,
              contingency_percentage DECIMAL(5, 2) DEFAULT NULL,
              valid_until DATE DEFAULT NULL,
              order_number VARCHAR(50) NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              contingence_amount DECIMAL(12, 2) DEFAULT NULL,
              contingence_reason LONGTEXT DEFAULT NULL,
              total_amount DECIMAL(12, 2) DEFAULT NULL,
              validated_at DATE DEFAULT NULL,
              created_at DATETIME DEFAULT NULL,
              updated_at DATETIME DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              contract_type VARCHAR(20) DEFAULT 'forfait' NOT NULL,
              expenses_rebillable TINYINT DEFAULT 0 NOT NULL,
              expense_management_fee_rate DECIMAL(5, 2) DEFAULT '0.00' NOT NULL,
              company_id INT NOT NULL,
              project_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_E52FFDEE551F0F81 (order_number),
              INDEX IDX_E52FFDEE979B1AD6 (company_id),
              INDEX IDX_E52FFDEE166D1F9C (project_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE performance_reviews (
              id INT AUTO_INCREMENT NOT NULL,
              year INT NOT NULL,
              status VARCHAR(50) NOT NULL,
              self_evaluation JSON DEFAULT NULL,
              manager_evaluation JSON DEFAULT NULL,
              objectives JSON DEFAULT NULL,
              overall_rating INT DEFAULT NULL,
              interview_date DATETIME DEFAULT NULL,
              comments LONGTEXT DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              validated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              manager_id INT NOT NULL,
              INDEX IDX_CAAC03557A19A357 (contributor_id),
              INDEX IDX_CAAC0355783E3463 (manager_id),
              INDEX idx_performance_review_year (year),
              INDEX idx_performance_review_status (status),
              INDEX idx_performancereview_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE planning (
              id INT AUTO_INCREMENT NOT NULL,
              start_date DATE NOT NULL,
              end_date DATE NOT NULL,
              daily_hours DECIMAL(4, 2) NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              project_id INT NOT NULL,
              profile_id INT DEFAULT NULL,
              INDEX IDX_D499BFF67A19A357 (contributor_id),
              INDEX IDX_D499BFF6166D1F9C (project_id),
              INDEX IDX_D499BFF6CCFA12B8 (profile_id),
              INDEX idx_planning_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE profiles (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(100) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              default_daily_rate DECIMAL(10, 2) DEFAULT NULL,
              cjm DECIMAL(10, 2) DEFAULT NULL,
              margin_coefficient DECIMAL(5, 2) DEFAULT '1.00',
              color VARCHAR(7) DEFAULT NULL,
              active TINYINT NOT NULL,
              company_id INT NOT NULL,
              UNIQUE INDEX UNIQ_8B3085305E237E06 (name),
              INDEX idx_profile_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_events (
              id INT AUTO_INCREMENT NOT NULL,
              event_type VARCHAR(50) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              data JSON DEFAULT NULL,
              created_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              project_id INT NOT NULL,
              actor_id INT DEFAULT NULL,
              INDEX IDX_4423BC00166D1F9C (project_id),
              INDEX IDX_4423BC0010DAF24A (actor_id),
              INDEX idx_project_created (project_id, created_at),
              INDEX idx_event_type (event_type),
              INDEX idx_projectevent_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_health_score (
              id INT AUTO_INCREMENT NOT NULL,
              score SMALLINT NOT NULL,
              health_level VARCHAR(20) NOT NULL,
              budget_score SMALLINT NOT NULL,
              timeline_score SMALLINT NOT NULL,
              velocity_score SMALLINT NOT NULL,
              quality_score SMALLINT NOT NULL,
              recommendations JSON DEFAULT NULL,
              details JSON DEFAULT NULL,
              calculated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              project_id INT NOT NULL,
              INDEX IDX_43FDF8F8166D1F9C (project_id),
              INDEX idx_project_date (project_id, calculated_at),
              INDEX idx_health_level (health_level),
              INDEX idx_projecthealthscore_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_sub_tasks (
              id INT AUTO_INCREMENT NOT NULL,
              title VARCHAR(255) NOT NULL,
              initial_estimated_hours DECIMAL(6, 2) NOT NULL,
              remaining_hours DECIMAL(6, 2) NOT NULL,
              status VARCHAR(20) NOT NULL,
              position INT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              project_id INT NOT NULL,
              task_id INT NOT NULL,
              assignee_id INT DEFAULT NULL,
              INDEX IDX_AD2044F9166D1F9C (project_id),
              INDEX IDX_AD2044F98DB60186 (task_id),
              INDEX IDX_AD2044F959EC7D60 (assignee_id),
              INDEX idx_projectsubtask_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_tasks (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              type VARCHAR(20) NOT NULL,
              is_default TINYINT NOT NULL,
              counts_for_profitability TINYINT NOT NULL,
              position INT NOT NULL,
              active TINYINT NOT NULL,
              estimated_hours_sold INT DEFAULT NULL,
              estimated_hours_revised INT DEFAULT NULL,
              progress_percentage INT NOT NULL,
              daily_rate DECIMAL(10, 2) DEFAULT NULL,
              start_date DATE DEFAULT NULL,
              end_date DATE DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              company_id INT NOT NULL,
              project_id INT NOT NULL,
              order_line_id INT DEFAULT NULL,
              assigned_contributor_id INT DEFAULT NULL,
              required_profile_id INT DEFAULT NULL,
              INDEX IDX_430D6C09166D1F9C (project_id),
              INDEX IDX_430D6C09BB01DC09 (order_line_id),
              INDEX IDX_430D6C097C1524E1 (assigned_contributor_id),
              INDEX IDX_430D6C09509DE452 (required_profile_id),
              INDEX idx_projecttask_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_technology_versions (
              id INT AUTO_INCREMENT NOT NULL,
              version VARCHAR(50) DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              company_id INT NOT NULL,
              project_id INT NOT NULL,
              technology_id INT NOT NULL,
              INDEX IDX_19C1B1E166D1F9C (project_id),
              INDEX IDX_19C1B1E4235D463 (technology_id),
              INDEX idx_projecttechnology_company (company_id),
              UNIQUE INDEX uniq_project_tech (project_id, technology_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projects (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(180) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              purchases_amount DECIMAL(12, 2) DEFAULT NULL,
              purchases_description LONGTEXT DEFAULT NULL,
              start_date DATE DEFAULT NULL,
              end_date DATE DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              is_internal TINYINT NOT NULL,
              project_type VARCHAR(20) NOT NULL,
              repo_links LONGTEXT DEFAULT NULL,
              env_links LONGTEXT DEFAULT NULL,
              db_access LONGTEXT DEFAULT NULL,
              ssh_access LONGTEXT DEFAULT NULL,
              ftp_access LONGTEXT DEFAULT NULL,
              company_id INT NOT NULL,
              client_id INT DEFAULT NULL,
              key_account_manager_id INT DEFAULT NULL,
              project_manager_id INT DEFAULT NULL,
              project_director_id INT DEFAULT NULL,
              sales_person_id INT DEFAULT NULL,
              service_category_id INT DEFAULT NULL,
              INDEX IDX_5C93B3A4979B1AD6 (company_id),
              INDEX IDX_5C93B3A419EB6921 (client_id),
              INDEX IDX_5C93B3A44DDC9A02 (key_account_manager_id),
              INDEX IDX_5C93B3A460984F51 (project_manager_id),
              INDEX IDX_5C93B3A44150449D (project_director_id),
              INDEX IDX_5C93B3A41D35E30E (sales_person_id),
              INDEX IDX_5C93B3A4DEDCBB4E (service_category_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_technologies (
              project_id INT NOT NULL,
              technology_id INT NOT NULL,
              INDEX IDX_666C1F7B166D1F9C (project_id),
              INDEX IDX_666C1F7B4235D463 (technology_id),
              PRIMARY KEY (project_id, technology_id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE running_timers (
              id INT AUTO_INCREMENT NOT NULL,
              started_at DATETIME NOT NULL,
              stopped_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              project_id INT NOT NULL,
              task_id INT DEFAULT NULL,
              sub_task_id INT DEFAULT NULL,
              INDEX IDX_5F84C1EC7A19A357 (contributor_id),
              INDEX IDX_5F84C1EC166D1F9C (project_id),
              INDEX IDX_5F84C1EC8DB60186 (task_id),
              INDEX IDX_5F84C1ECF26E5D72 (sub_task_id),
              INDEX idx_runningtimer_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE saas_distribution_providers (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              type VARCHAR(50) DEFAULT 'other' NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              active TINYINT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              INDEX idx_provider_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE saas_providers (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              website VARCHAR(500) DEFAULT NULL,
              contact_email VARCHAR(255) DEFAULT NULL,
              contact_phone VARCHAR(50) DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              active TINYINT DEFAULT 1 NOT NULL,
              logo_url VARCHAR(500) DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              INDEX idx_saasprovider_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE saas_services (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              category VARCHAR(100) DEFAULT NULL,
              service_url VARCHAR(500) DEFAULT NULL,
              logo_url VARCHAR(500) DEFAULT NULL,
              default_monthly_price DECIMAL(10, 2) DEFAULT NULL,
              default_yearly_price DECIMAL(10, 2) DEFAULT NULL,
              currency VARCHAR(3) DEFAULT 'EUR' NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              active TINYINT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              provider_id INT DEFAULT NULL,
              INDEX idx_saas_service_provider (provider_id),
              INDEX idx_saasservice_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE saas_subscriptions (
              id INT AUTO_INCREMENT NOT NULL,
              custom_name VARCHAR(255) DEFAULT NULL,
              billing_period VARCHAR(20) NOT NULL,
              price DECIMAL(10, 2) NOT NULL,
              currency VARCHAR(3) DEFAULT 'EUR' NOT NULL,
              quantity INT DEFAULT 1 NOT NULL,
              start_date DATE NOT NULL,
              end_date DATE DEFAULT NULL,
              next_renewal_date DATE NOT NULL,
              last_renewal_date DATE DEFAULT NULL,
              auto_renewal TINYINT DEFAULT 1 NOT NULL,
              status VARCHAR(20) DEFAULT 'active' NOT NULL,
              external_reference VARCHAR(255) DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              service_id INT NOT NULL,
              INDEX idx_saas_subscription_service (service_id),
              INDEX idx_saas_subscription_status (status),
              INDEX idx_saas_subscription_renewal (next_renewal_date),
              INDEX idx_saassubscription_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE saas_subscriptions_v2 (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              category VARCHAR(100) DEFAULT NULL,
              service_url VARCHAR(500) DEFAULT NULL,
              logo_url VARCHAR(500) DEFAULT NULL,
              billing_period VARCHAR(20) NOT NULL,
              price DECIMAL(10, 2) NOT NULL,
              currency VARCHAR(3) DEFAULT 'EUR' NOT NULL,
              quantity INT DEFAULT 1 NOT NULL,
              start_date DATE NOT NULL,
              end_date DATE DEFAULT NULL,
              next_renewal_date DATE DEFAULT NULL,
              last_renewal_date DATE DEFAULT NULL,
              auto_renewal TINYINT DEFAULT 1 NOT NULL,
              status VARCHAR(20) DEFAULT 'active' NOT NULL,
              external_reference VARCHAR(255) DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              active TINYINT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              vendor_id INT NOT NULL,
              provider_id INT DEFAULT NULL,
              INDEX idx_subscription_vendor (vendor_id),
              INDEX idx_subscription_provider (provider_id),
              INDEX idx_subscription_status (status),
              INDEX idx_subscription_renewal (next_renewal_date),
              INDEX idx_subscription_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE saas_vendors (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(255) NOT NULL,
              website VARCHAR(500) DEFAULT NULL,
              contact_email VARCHAR(255) DEFAULT NULL,
              contact_phone VARCHAR(50) DEFAULT NULL,
              logo_url VARCHAR(500) DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              active TINYINT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              INDEX idx_vendor_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE scheduler_entries (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(150) NOT NULL,
              cron_expression VARCHAR(100) NOT NULL,
              command VARCHAR(255) NOT NULL,
              payload JSON DEFAULT NULL,
              enabled TINYINT DEFAULT 1 NOT NULL,
              timezone VARCHAR(50) DEFAULT 'Europe/Paris' NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              INDEX idx_schedulerentry_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE service_categories (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(100) NOT NULL,
              description VARCHAR(255) DEFAULT NULL,
              color VARCHAR(7) DEFAULT NULL,
              active TINYINT NOT NULL,
              company_id INT NOT NULL,
              INDEX idx_servicecategory_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE skills (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(100) NOT NULL,
              category VARCHAR(50) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              active TINYINT DEFAULT 1 NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              UNIQUE INDEX UNIQ_D53116705E237E06 (name),
              INDEX idx_skill_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE technologies (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(100) NOT NULL,
              category VARCHAR(50) NOT NULL,
              color VARCHAR(7) DEFAULT NULL,
              active TINYINT NOT NULL,
              company_id INT NOT NULL,
              INDEX idx_technology_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE timesheets (
              id INT AUTO_INCREMENT NOT NULL,
              date DATE NOT NULL,
              hours DECIMAL(5, 2) NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              project_id INT NOT NULL,
              task_id INT DEFAULT NULL,
              sub_task_id INT DEFAULT NULL,
              INDEX IDX_9AC77D2E979B1AD6 (company_id),
              INDEX IDX_9AC77D2E7A19A357 (contributor_id),
              INDEX IDX_9AC77D2E166D1F9C (project_id),
              INDEX IDX_9AC77D2E8DB60186 (task_id),
              INDEX IDX_9AC77D2EF26E5D72 (sub_task_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
              id INT AUTO_INCREMENT NOT NULL,
              email VARCHAR(180) NOT NULL,
              roles JSON NOT NULL,
              password VARCHAR(255) NOT NULL,
              first_name VARCHAR(100) NOT NULL,
              last_name VARCHAR(100) NOT NULL,
              phone VARCHAR(32) DEFAULT NULL,
              phone_work VARCHAR(32) DEFAULT NULL,
              phone_personal VARCHAR(32) DEFAULT NULL,
              address LONGTEXT DEFAULT NULL,
              created_at DATETIME DEFAULT NULL,
              updated_at DATETIME DEFAULT NULL,
              avatar VARCHAR(255) DEFAULT NULL,
              totp_secret VARCHAR(255) DEFAULT NULL,
              totp_enabled TINYINT NOT NULL,
              last_login_at DATETIME DEFAULT NULL,
              last_login_ip VARCHAR(45) DEFAULT NULL,
              company_id INT NOT NULL,
              UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
              INDEX idx_user_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE vacations (
              id INT AUTO_INCREMENT NOT NULL,
              start_date DATE NOT NULL,
              end_date DATE NOT NULL,
              type VARCHAR(50) NOT NULL,
              reason LONGTEXT DEFAULT NULL,
              status VARCHAR(20) NOT NULL,
              daily_hours DECIMAL(4, 2) NOT NULL,
              created_at DATETIME NOT NULL,
              approved_at DATETIME DEFAULT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              approved_by_id INT DEFAULT NULL,
              INDEX IDX_3B8290677A19A357 (contributor_id),
              INDEX IDX_3B8290672D234F6A (approved_by_id),
              INDEX idx_vacation_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE xp_history (
              id INT AUTO_INCREMENT NOT NULL,
              xp_amount INT NOT NULL,
              source VARCHAR(100) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              metadata JSON DEFAULT NULL,
              gained_at DATETIME NOT NULL,
              company_id INT NOT NULL,
              contributor_id INT NOT NULL,
              INDEX IDX_C06720907A19A357 (contributor_id),
              INDEX idx_contributor_gained (contributor_id, gained_at),
              INDEX idx_xphistory_company (company_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
              id BIGINT AUTO_INCREMENT NOT NULL,
              body LONGTEXT NOT NULL,
              headers LONGTEXT NOT NULL,
              queue_name VARCHAR(190) NOT NULL,
              created_at DATETIME NOT NULL,
              available_at DATETIME NOT NULL,
              delivered_at DATETIME DEFAULT NULL,
              INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (
                queue_name, available_at, delivered_at,
                id
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              account_deletion_requests
            ADD
              CONSTRAINT FK_748FBDF6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              account_deletion_requests
            ADD
              CONSTRAINT FK_748FBDF6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              achievements
            ADD
              CONSTRAINT FK_D1227EFE979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              achievements
            ADD
              CONSTRAINT FK_D1227EFE7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              achievements
            ADD
              CONSTRAINT FK_D1227EFEF7A2C2FC FOREIGN KEY (badge_id) REFERENCES badges (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              badges
            ADD
              CONSTRAINT FK_78F6539A979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              billing_markers
            ADD
              CONSTRAINT FK_2AA754E7979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              billing_markers
            ADD
              CONSTRAINT FK_2AA754E7A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES order_payment_schedules (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              billing_markers
            ADD
              CONSTRAINT FK_2AA754E78D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              business_units
            ADD
              CONSTRAINT FK_975193F6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              business_units
            ADD
              CONSTRAINT FK_975193F6727ACA70 FOREIGN KEY (parent_id) REFERENCES business_units (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              business_units
            ADD
              CONSTRAINT FK_975193F6783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              client_contacts
            ADD
              CONSTRAINT FK_1DA625B6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              client_contacts
            ADD
              CONSTRAINT FK_1DA625B619EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              clients
            ADD
              CONSTRAINT FK_C82E74979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              companies
            ADD
              CONSTRAINT FK_8244AA3A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE RESTRICT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_settings
            ADD
              CONSTRAINT FK_FDD2B5A8979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_progress
            ADD
              CONSTRAINT FK_14C77770979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_progress
            ADD
              CONSTRAINT FK_14C777707A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_satisfactions
            ADD
              CONSTRAINT FK_77E27071979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_satisfactions
            ADD
              CONSTRAINT FK_77E270717A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_skills
            ADD
              CONSTRAINT FK_A0CA02C8979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_skills
            ADD
              CONSTRAINT FK_A0CA02C87A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_skills
            ADD
              CONSTRAINT FK_A0CA02C85585C142 FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributors
            ADD
              CONSTRAINT FK_72D26262979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributors
            ADD
              CONSTRAINT FK_72D26262A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributors
            ADD
              CONSTRAINT FK_72D26262783E3463 FOREIGN KEY (manager_id) REFERENCES contributors (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_profiles
            ADD
              CONSTRAINT FK_BDF600067A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contributor_profiles
            ADD
              CONSTRAINT FK_BDF60006CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              cookie_consents
            ADD
              CONSTRAINT FK_FCDE2BEF979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              cookie_consents
            ADD
              CONSTRAINT FK_FCDE2BEFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dim_contributor
            ADD
              CONSTRAINT FK_8BC20A2C979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dim_contributor
            ADD
              CONSTRAINT FK_8BC20A2CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dim_profile
            ADD
              CONSTRAINT FK_9B698904979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dim_profile
            ADD
              CONSTRAINT FK_9B698904CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dim_project_type
            ADD
              CONSTRAINT FK_EEC09CB1979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dim_time
            ADD
              CONSTRAINT FK_6F547BD9979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_periods
            ADD
              CONSTRAINT FK_B996D77B979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_periods
            ADD
              CONSTRAINT FK_B996D77B7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_period_profiles
            ADD
              CONSTRAINT FK_A643DBB15A128608 FOREIGN KEY (employment_period_id) REFERENCES employment_periods (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              employment_period_profiles
            ADD
              CONSTRAINT FK_A643DBB1CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              expense_reports
            ADD
              CONSTRAINT FK_9C04EC7F979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              expense_reports
            ADD
              CONSTRAINT FK_9C04EC7F7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              expense_reports
            ADD
              CONSTRAINT FK_9C04EC7F166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              expense_reports
            ADD
              CONSTRAINT FK_9C04EC7F8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              expense_reports
            ADD
              CONSTRAINT FK_9C04EC7FB0644AEC FOREIGN KEY (validator_id) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_forecast
            ADD
              CONSTRAINT FK_C6549F37979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A94979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A9444D4FE30 FOREIGN KEY (dim_time_id) REFERENCES dim_time (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A94E44D565F FOREIGN KEY (dim_project_type_id) REFERENCES dim_project_type (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A94EC5B4665 FOREIGN KEY (dim_project_manager_id) REFERENCES dim_contributor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A94AA2A35A7 FOREIGN KEY (dim_sales_person_id) REFERENCES dim_contributor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A9460687321 FOREIGN KEY (dim_project_director_id) REFERENCES dim_contributor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A94166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_project_metrics
            ADD
              CONSTRAINT FK_27991A948D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_staffing_metrics
            ADD
              CONSTRAINT FK_F58C0D48979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_staffing_metrics
            ADD
              CONSTRAINT FK_F58C0D4844D4FE30 FOREIGN KEY (dim_time_id) REFERENCES dim_time (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_staffing_metrics
            ADD
              CONSTRAINT FK_F58C0D48BF679789 FOREIGN KEY (dim_profile_id) REFERENCES dim_profile (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fact_staffing_metrics
            ADD
              CONSTRAINT FK_F58C0D487A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invoice_lines
            ADD
              CONSTRAINT FK_72DBDC23979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invoice_lines
            ADD
              CONSTRAINT FK_72DBDC232989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invoices
            ADD
              CONSTRAINT FK_6A2F2F95979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invoices
            ADD
              CONSTRAINT FK_6A2F2F958D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invoices
            ADD
              CONSTRAINT FK_6A2F2F95166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invoices
            ADD
              CONSTRAINT FK_6A2F2F9519EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invoices
            ADD
              CONSTRAINT FK_6A2F2F955287120F FOREIGN KEY (payment_schedule_id) REFERENCES order_payment_schedules (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lead_captures
            ADD
              CONSTRAINT FK_61B6A559979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notification_preferences
            ADD
              CONSTRAINT FK_3CAA95B4979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notification_preferences
            ADD
              CONSTRAINT FK_3CAA95B4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notification_settings
            ADD
              CONSTRAINT FK_B0559860979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notifications
            ADD
              CONSTRAINT FK_6000B0D3979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notifications
            ADD
              CONSTRAINT FK_6000B0D3E92F8F78 FOREIGN KEY (recipient_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              nps_surveys
            ADD
              CONSTRAINT FK_E88066E9979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              nps_surveys
            ADD
              CONSTRAINT FK_E88066E9166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              onboarding_tasks
            ADD
              CONSTRAINT FK_6DCA087B979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              onboarding_tasks
            ADD
              CONSTRAINT FK_6DCA087B7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              onboarding_tasks
            ADD
              CONSTRAINT FK_6DCA087B5DA0FB8 FOREIGN KEY (template_id) REFERENCES onboarding_templates (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              onboarding_templates
            ADD
              CONSTRAINT FK_A8917FF4979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              onboarding_templates
            ADD
              CONSTRAINT FK_A8917FF4CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_lines
            ADD
              CONSTRAINT FK_CC9FF86B979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_lines
            ADD
              CONSTRAINT FK_CC9FF86BD823E37A FOREIGN KEY (section_id) REFERENCES order_sections (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_lines
            ADD
              CONSTRAINT FK_CC9FF86BCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_payment_schedules
            ADD
              CONSTRAINT FK_6671B9FD979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_payment_schedules
            ADD
              CONSTRAINT FK_6671B9FD8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_sections
            ADD
              CONSTRAINT FK_CA6EA129979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_sections
            ADD
              CONSTRAINT FK_CA6EA1298D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_tasks
            ADD
              CONSTRAINT FK_D3C6116A979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_tasks
            ADD
              CONSTRAINT FK_D3C6116A8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              order_tasks
            ADD
              CONSTRAINT FK_D3C6116ACCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              orders
            ADD
              CONSTRAINT FK_E52FFDEE979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              orders
            ADD
              CONSTRAINT FK_E52FFDEE166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              performance_reviews
            ADD
              CONSTRAINT FK_CAAC0355979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              performance_reviews
            ADD
              CONSTRAINT FK_CAAC03557A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              performance_reviews
            ADD
              CONSTRAINT FK_CAAC0355783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE RESTRICT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              planning
            ADD
              CONSTRAINT FK_D499BFF6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              planning
            ADD
              CONSTRAINT FK_D499BFF67A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              planning
            ADD
              CONSTRAINT FK_D499BFF6166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              planning
            ADD
              CONSTRAINT FK_D499BFF6CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              profiles
            ADD
              CONSTRAINT FK_8B308530979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_events
            ADD
              CONSTRAINT FK_4423BC00979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_events
            ADD
              CONSTRAINT FK_4423BC00166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_events
            ADD
              CONSTRAINT FK_4423BC0010DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_health_score
            ADD
              CONSTRAINT FK_43FDF8F8979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_health_score
            ADD
              CONSTRAINT FK_43FDF8F8166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_sub_tasks
            ADD
              CONSTRAINT FK_AD2044F9979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_sub_tasks
            ADD
              CONSTRAINT FK_AD2044F9166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_sub_tasks
            ADD
              CONSTRAINT FK_AD2044F98DB60186 FOREIGN KEY (task_id) REFERENCES project_tasks (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_sub_tasks
            ADD
              CONSTRAINT FK_AD2044F959EC7D60 FOREIGN KEY (assignee_id) REFERENCES contributors (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_tasks
            ADD
              CONSTRAINT FK_430D6C09979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_tasks
            ADD
              CONSTRAINT FK_430D6C09166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_tasks
            ADD
              CONSTRAINT FK_430D6C09BB01DC09 FOREIGN KEY (order_line_id) REFERENCES order_lines (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_tasks
            ADD
              CONSTRAINT FK_430D6C097C1524E1 FOREIGN KEY (assigned_contributor_id) REFERENCES contributors (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_tasks
            ADD
              CONSTRAINT FK_430D6C09509DE452 FOREIGN KEY (required_profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_technology_versions
            ADD
              CONSTRAINT FK_19C1B1E979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_technology_versions
            ADD
              CONSTRAINT FK_19C1B1E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_technology_versions
            ADD
              CONSTRAINT FK_19C1B1E4235D463 FOREIGN KEY (technology_id) REFERENCES technologies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects
            ADD
              CONSTRAINT FK_5C93B3A4979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects
            ADD
              CONSTRAINT FK_5C93B3A419EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects
            ADD
              CONSTRAINT FK_5C93B3A44DDC9A02 FOREIGN KEY (key_account_manager_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects
            ADD
              CONSTRAINT FK_5C93B3A460984F51 FOREIGN KEY (project_manager_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects
            ADD
              CONSTRAINT FK_5C93B3A44150449D FOREIGN KEY (project_director_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects
            ADD
              CONSTRAINT FK_5C93B3A41D35E30E FOREIGN KEY (sales_person_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              projects
            ADD
              CONSTRAINT FK_5C93B3A4DEDCBB4E FOREIGN KEY (service_category_id) REFERENCES service_categories (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_technologies
            ADD
              CONSTRAINT FK_666C1F7B166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project_technologies
            ADD
              CONSTRAINT FK_666C1F7B4235D463 FOREIGN KEY (technology_id) REFERENCES technologies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              running_timers
            ADD
              CONSTRAINT FK_5F84C1EC979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              running_timers
            ADD
              CONSTRAINT FK_5F84C1EC7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              running_timers
            ADD
              CONSTRAINT FK_5F84C1EC166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              running_timers
            ADD
              CONSTRAINT FK_5F84C1EC8DB60186 FOREIGN KEY (task_id) REFERENCES project_tasks (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              running_timers
            ADD
              CONSTRAINT FK_5F84C1ECF26E5D72 FOREIGN KEY (sub_task_id) REFERENCES project_sub_tasks (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_distribution_providers
            ADD
              CONSTRAINT FK_6B16AE71979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_providers
            ADD
              CONSTRAINT FK_CD4E56B979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_services
            ADD
              CONSTRAINT FK_8C62B16B979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_services
            ADD
              CONSTRAINT FK_8C62B16BA53A8AA FOREIGN KEY (provider_id) REFERENCES saas_providers (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_subscriptions
            ADD
              CONSTRAINT FK_4A220501979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_subscriptions
            ADD
              CONSTRAINT FK_4A220501ED5CA9E6 FOREIGN KEY (service_id) REFERENCES saas_services (id) ON DELETE RESTRICT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_subscriptions_v2
            ADD
              CONSTRAINT FK_80C6E0B3979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_subscriptions_v2
            ADD
              CONSTRAINT FK_80C6E0B3F603EE73 FOREIGN KEY (vendor_id) REFERENCES saas_vendors (id) ON DELETE RESTRICT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_subscriptions_v2
            ADD
              CONSTRAINT FK_80C6E0B3A53A8AA FOREIGN KEY (provider_id) REFERENCES saas_distribution_providers (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saas_vendors
            ADD
              CONSTRAINT FK_101FC8C8979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              scheduler_entries
            ADD
              CONSTRAINT FK_96E5DE7A979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              service_categories
            ADD
              CONSTRAINT FK_ACA27FDC979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              skills
            ADD
              CONSTRAINT FK_D5311670979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              technologies
            ADD
              CONSTRAINT FK_4CCBFB18979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              timesheets
            ADD
              CONSTRAINT FK_9AC77D2E979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              timesheets
            ADD
              CONSTRAINT FK_9AC77D2E7A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              timesheets
            ADD
              CONSTRAINT FK_9AC77D2E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              timesheets
            ADD
              CONSTRAINT FK_9AC77D2E8DB60186 FOREIGN KEY (task_id) REFERENCES project_tasks (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              timesheets
            ADD
              CONSTRAINT FK_9AC77D2EF26E5D72 FOREIGN KEY (sub_task_id) REFERENCES project_sub_tasks (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              users
            ADD
              CONSTRAINT FK_1483A5E9979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              vacations
            ADD
              CONSTRAINT FK_3B829067979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              vacations
            ADD
              CONSTRAINT FK_3B8290677A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              vacations
            ADD
              CONSTRAINT FK_3B8290672D234F6A FOREIGN KEY (approved_by_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              xp_history
            ADD
              CONSTRAINT FK_C0672090979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              xp_history
            ADD
              CONSTRAINT FK_C06720907A19A357 FOREIGN KEY (contributor_id) REFERENCES contributors (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_deletion_requests DROP FOREIGN KEY FK_748FBDF6979B1AD6');
        $this->addSql('ALTER TABLE account_deletion_requests DROP FOREIGN KEY FK_748FBDF6A76ED395');
        $this->addSql('ALTER TABLE achievements DROP FOREIGN KEY FK_D1227EFE979B1AD6');
        $this->addSql('ALTER TABLE achievements DROP FOREIGN KEY FK_D1227EFE7A19A357');
        $this->addSql('ALTER TABLE achievements DROP FOREIGN KEY FK_D1227EFEF7A2C2FC');
        $this->addSql('ALTER TABLE badges DROP FOREIGN KEY FK_78F6539A979B1AD6');
        $this->addSql('ALTER TABLE billing_markers DROP FOREIGN KEY FK_2AA754E7979B1AD6');
        $this->addSql('ALTER TABLE billing_markers DROP FOREIGN KEY FK_2AA754E7A40BC2D5');
        $this->addSql('ALTER TABLE billing_markers DROP FOREIGN KEY FK_2AA754E78D9F6D38');
        $this->addSql('ALTER TABLE business_units DROP FOREIGN KEY FK_975193F6979B1AD6');
        $this->addSql('ALTER TABLE business_units DROP FOREIGN KEY FK_975193F6727ACA70');
        $this->addSql('ALTER TABLE business_units DROP FOREIGN KEY FK_975193F6783E3463');
        $this->addSql('ALTER TABLE client_contacts DROP FOREIGN KEY FK_1DA625B6979B1AD6');
        $this->addSql('ALTER TABLE client_contacts DROP FOREIGN KEY FK_1DA625B619EB6921');
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E74979B1AD6');
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A7E3C61F9');
        $this->addSql('ALTER TABLE company_settings DROP FOREIGN KEY FK_FDD2B5A8979B1AD6');
        $this->addSql('ALTER TABLE contributor_progress DROP FOREIGN KEY FK_14C77770979B1AD6');
        $this->addSql('ALTER TABLE contributor_progress DROP FOREIGN KEY FK_14C777707A19A357');
        $this->addSql('ALTER TABLE contributor_satisfactions DROP FOREIGN KEY FK_77E27071979B1AD6');
        $this->addSql('ALTER TABLE contributor_satisfactions DROP FOREIGN KEY FK_77E270717A19A357');
        $this->addSql('ALTER TABLE contributor_skills DROP FOREIGN KEY FK_A0CA02C8979B1AD6');
        $this->addSql('ALTER TABLE contributor_skills DROP FOREIGN KEY FK_A0CA02C87A19A357');
        $this->addSql('ALTER TABLE contributor_skills DROP FOREIGN KEY FK_A0CA02C85585C142');
        $this->addSql('ALTER TABLE contributors DROP FOREIGN KEY FK_72D26262979B1AD6');
        $this->addSql('ALTER TABLE contributors DROP FOREIGN KEY FK_72D26262A76ED395');
        $this->addSql('ALTER TABLE contributors DROP FOREIGN KEY FK_72D26262783E3463');
        $this->addSql('ALTER TABLE contributor_profiles DROP FOREIGN KEY FK_BDF600067A19A357');
        $this->addSql('ALTER TABLE contributor_profiles DROP FOREIGN KEY FK_BDF60006CCFA12B8');
        $this->addSql('ALTER TABLE cookie_consents DROP FOREIGN KEY FK_FCDE2BEF979B1AD6');
        $this->addSql('ALTER TABLE cookie_consents DROP FOREIGN KEY FK_FCDE2BEFA76ED395');
        $this->addSql('ALTER TABLE dim_contributor DROP FOREIGN KEY FK_8BC20A2C979B1AD6');
        $this->addSql('ALTER TABLE dim_contributor DROP FOREIGN KEY FK_8BC20A2CA76ED395');
        $this->addSql('ALTER TABLE dim_profile DROP FOREIGN KEY FK_9B698904979B1AD6');
        $this->addSql('ALTER TABLE dim_profile DROP FOREIGN KEY FK_9B698904CCFA12B8');
        $this->addSql('ALTER TABLE dim_project_type DROP FOREIGN KEY FK_EEC09CB1979B1AD6');
        $this->addSql('ALTER TABLE dim_time DROP FOREIGN KEY FK_6F547BD9979B1AD6');
        $this->addSql('ALTER TABLE employment_periods DROP FOREIGN KEY FK_B996D77B979B1AD6');
        $this->addSql('ALTER TABLE employment_periods DROP FOREIGN KEY FK_B996D77B7A19A357');
        $this->addSql('ALTER TABLE employment_period_profiles DROP FOREIGN KEY FK_A643DBB15A128608');
        $this->addSql('ALTER TABLE employment_period_profiles DROP FOREIGN KEY FK_A643DBB1CCFA12B8');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9C04EC7F979B1AD6');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9C04EC7F7A19A357');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9C04EC7F166D1F9C');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9C04EC7F8D9F6D38');
        $this->addSql('ALTER TABLE expense_reports DROP FOREIGN KEY FK_9C04EC7FB0644AEC');
        $this->addSql('ALTER TABLE fact_forecast DROP FOREIGN KEY FK_C6549F37979B1AD6');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94979B1AD6');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A9444D4FE30');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94E44D565F');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94EC5B4665');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94AA2A35A7');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A9460687321');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A94166D1F9C');
        $this->addSql('ALTER TABLE fact_project_metrics DROP FOREIGN KEY FK_27991A948D9F6D38');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D48979B1AD6');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D4844D4FE30');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D48BF679789');
        $this->addSql('ALTER TABLE fact_staffing_metrics DROP FOREIGN KEY FK_F58C0D487A19A357');
        $this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_72DBDC23979B1AD6');
        $this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_72DBDC232989F1FD');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95979B1AD6');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F958D9F6D38');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95166D1F9C');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F9519EB6921');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F955287120F');
        $this->addSql('ALTER TABLE lead_captures DROP FOREIGN KEY FK_61B6A559979B1AD6');
        $this->addSql('ALTER TABLE notification_preferences DROP FOREIGN KEY FK_3CAA95B4979B1AD6');
        $this->addSql('ALTER TABLE notification_preferences DROP FOREIGN KEY FK_3CAA95B4A76ED395');
        $this->addSql('ALTER TABLE notification_settings DROP FOREIGN KEY FK_B0559860979B1AD6');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3979B1AD6');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3E92F8F78');
        $this->addSql('ALTER TABLE nps_surveys DROP FOREIGN KEY FK_E88066E9979B1AD6');
        $this->addSql('ALTER TABLE nps_surveys DROP FOREIGN KEY FK_E88066E9166D1F9C');
        $this->addSql('ALTER TABLE onboarding_tasks DROP FOREIGN KEY FK_6DCA087B979B1AD6');
        $this->addSql('ALTER TABLE onboarding_tasks DROP FOREIGN KEY FK_6DCA087B7A19A357');
        $this->addSql('ALTER TABLE onboarding_tasks DROP FOREIGN KEY FK_6DCA087B5DA0FB8');
        $this->addSql('ALTER TABLE onboarding_templates DROP FOREIGN KEY FK_A8917FF4979B1AD6');
        $this->addSql('ALTER TABLE onboarding_templates DROP FOREIGN KEY FK_A8917FF4CCFA12B8');
        $this->addSql('ALTER TABLE order_lines DROP FOREIGN KEY FK_CC9FF86B979B1AD6');
        $this->addSql('ALTER TABLE order_lines DROP FOREIGN KEY FK_CC9FF86BD823E37A');
        $this->addSql('ALTER TABLE order_lines DROP FOREIGN KEY FK_CC9FF86BCCFA12B8');
        $this->addSql('ALTER TABLE order_payment_schedules DROP FOREIGN KEY FK_6671B9FD979B1AD6');
        $this->addSql('ALTER TABLE order_payment_schedules DROP FOREIGN KEY FK_6671B9FD8D9F6D38');
        $this->addSql('ALTER TABLE order_sections DROP FOREIGN KEY FK_CA6EA129979B1AD6');
        $this->addSql('ALTER TABLE order_sections DROP FOREIGN KEY FK_CA6EA1298D9F6D38');
        $this->addSql('ALTER TABLE order_tasks DROP FOREIGN KEY FK_D3C6116A979B1AD6');
        $this->addSql('ALTER TABLE order_tasks DROP FOREIGN KEY FK_D3C6116A8D9F6D38');
        $this->addSql('ALTER TABLE order_tasks DROP FOREIGN KEY FK_D3C6116ACCFA12B8');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE979B1AD6');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE166D1F9C');
        $this->addSql('ALTER TABLE performance_reviews DROP FOREIGN KEY FK_CAAC0355979B1AD6');
        $this->addSql('ALTER TABLE performance_reviews DROP FOREIGN KEY FK_CAAC03557A19A357');
        $this->addSql('ALTER TABLE performance_reviews DROP FOREIGN KEY FK_CAAC0355783E3463');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6979B1AD6');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF67A19A357');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6166D1F9C');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6CCFA12B8');
        $this->addSql('ALTER TABLE profiles DROP FOREIGN KEY FK_8B308530979B1AD6');
        $this->addSql('ALTER TABLE project_events DROP FOREIGN KEY FK_4423BC00979B1AD6');
        $this->addSql('ALTER TABLE project_events DROP FOREIGN KEY FK_4423BC00166D1F9C');
        $this->addSql('ALTER TABLE project_events DROP FOREIGN KEY FK_4423BC0010DAF24A');
        $this->addSql('ALTER TABLE project_health_score DROP FOREIGN KEY FK_43FDF8F8979B1AD6');
        $this->addSql('ALTER TABLE project_health_score DROP FOREIGN KEY FK_43FDF8F8166D1F9C');
        $this->addSql('ALTER TABLE project_sub_tasks DROP FOREIGN KEY FK_AD2044F9979B1AD6');
        $this->addSql('ALTER TABLE project_sub_tasks DROP FOREIGN KEY FK_AD2044F9166D1F9C');
        $this->addSql('ALTER TABLE project_sub_tasks DROP FOREIGN KEY FK_AD2044F98DB60186');
        $this->addSql('ALTER TABLE project_sub_tasks DROP FOREIGN KEY FK_AD2044F959EC7D60');
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_430D6C09979B1AD6');
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_430D6C09166D1F9C');
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_430D6C09BB01DC09');
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_430D6C097C1524E1');
        $this->addSql('ALTER TABLE project_tasks DROP FOREIGN KEY FK_430D6C09509DE452');
        $this->addSql('ALTER TABLE project_technology_versions DROP FOREIGN KEY FK_19C1B1E979B1AD6');
        $this->addSql('ALTER TABLE project_technology_versions DROP FOREIGN KEY FK_19C1B1E166D1F9C');
        $this->addSql('ALTER TABLE project_technology_versions DROP FOREIGN KEY FK_19C1B1E4235D463');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4979B1AD6');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A419EB6921');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A44DDC9A02');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A460984F51');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A44150449D');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A41D35E30E');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4DEDCBB4E');
        $this->addSql('ALTER TABLE project_technologies DROP FOREIGN KEY FK_666C1F7B166D1F9C');
        $this->addSql('ALTER TABLE project_technologies DROP FOREIGN KEY FK_666C1F7B4235D463');
        $this->addSql('ALTER TABLE running_timers DROP FOREIGN KEY FK_5F84C1EC979B1AD6');
        $this->addSql('ALTER TABLE running_timers DROP FOREIGN KEY FK_5F84C1EC7A19A357');
        $this->addSql('ALTER TABLE running_timers DROP FOREIGN KEY FK_5F84C1EC166D1F9C');
        $this->addSql('ALTER TABLE running_timers DROP FOREIGN KEY FK_5F84C1EC8DB60186');
        $this->addSql('ALTER TABLE running_timers DROP FOREIGN KEY FK_5F84C1ECF26E5D72');
        $this->addSql('ALTER TABLE saas_distribution_providers DROP FOREIGN KEY FK_6B16AE71979B1AD6');
        $this->addSql('ALTER TABLE saas_providers DROP FOREIGN KEY FK_CD4E56B979B1AD6');
        $this->addSql('ALTER TABLE saas_services DROP FOREIGN KEY FK_8C62B16B979B1AD6');
        $this->addSql('ALTER TABLE saas_services DROP FOREIGN KEY FK_8C62B16BA53A8AA');
        $this->addSql('ALTER TABLE saas_subscriptions DROP FOREIGN KEY FK_4A220501979B1AD6');
        $this->addSql('ALTER TABLE saas_subscriptions DROP FOREIGN KEY FK_4A220501ED5CA9E6');
        $this->addSql('ALTER TABLE saas_subscriptions_v2 DROP FOREIGN KEY FK_80C6E0B3979B1AD6');
        $this->addSql('ALTER TABLE saas_subscriptions_v2 DROP FOREIGN KEY FK_80C6E0B3F603EE73');
        $this->addSql('ALTER TABLE saas_subscriptions_v2 DROP FOREIGN KEY FK_80C6E0B3A53A8AA');
        $this->addSql('ALTER TABLE saas_vendors DROP FOREIGN KEY FK_101FC8C8979B1AD6');
        $this->addSql('ALTER TABLE scheduler_entries DROP FOREIGN KEY FK_96E5DE7A979B1AD6');
        $this->addSql('ALTER TABLE service_categories DROP FOREIGN KEY FK_ACA27FDC979B1AD6');
        $this->addSql('ALTER TABLE skills DROP FOREIGN KEY FK_D5311670979B1AD6');
        $this->addSql('ALTER TABLE technologies DROP FOREIGN KEY FK_4CCBFB18979B1AD6');
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2E979B1AD6');
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2E7A19A357');
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2E166D1F9C');
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2E8DB60186');
        $this->addSql('ALTER TABLE timesheets DROP FOREIGN KEY FK_9AC77D2EF26E5D72');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9979B1AD6');
        $this->addSql('ALTER TABLE vacations DROP FOREIGN KEY FK_3B829067979B1AD6');
        $this->addSql('ALTER TABLE vacations DROP FOREIGN KEY FK_3B8290677A19A357');
        $this->addSql('ALTER TABLE vacations DROP FOREIGN KEY FK_3B8290672D234F6A');
        $this->addSql('ALTER TABLE xp_history DROP FOREIGN KEY FK_C0672090979B1AD6');
        $this->addSql('ALTER TABLE xp_history DROP FOREIGN KEY FK_C06720907A19A357');
        $this->addSql('DROP TABLE account_deletion_requests');
        $this->addSql('DROP TABLE achievements');
        $this->addSql('DROP TABLE badges');
        $this->addSql('DROP TABLE billing_markers');
        $this->addSql('DROP TABLE business_units');
        $this->addSql('DROP TABLE client_contacts');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP TABLE company_settings');
        $this->addSql('DROP TABLE contributor_progress');
        $this->addSql('DROP TABLE contributor_satisfactions');
        $this->addSql('DROP TABLE contributor_skills');
        $this->addSql('DROP TABLE contributors');
        $this->addSql('DROP TABLE contributor_profiles');
        $this->addSql('DROP TABLE cookie_consents');
        $this->addSql('DROP TABLE dim_contributor');
        $this->addSql('DROP TABLE dim_profile');
        $this->addSql('DROP TABLE dim_project_type');
        $this->addSql('DROP TABLE dim_time');
        $this->addSql('DROP TABLE employment_periods');
        $this->addSql('DROP TABLE employment_period_profiles');
        $this->addSql('DROP TABLE expense_reports');
        $this->addSql('DROP TABLE fact_forecast');
        $this->addSql('DROP TABLE fact_project_metrics');
        $this->addSql('DROP TABLE fact_staffing_metrics');
        $this->addSql('DROP TABLE invoice_lines');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('DROP TABLE lead_captures');
        $this->addSql('DROP TABLE notification_preferences');
        $this->addSql('DROP TABLE notification_settings');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE nps_surveys');
        $this->addSql('DROP TABLE onboarding_tasks');
        $this->addSql('DROP TABLE onboarding_templates');
        $this->addSql('DROP TABLE order_lines');
        $this->addSql('DROP TABLE order_payment_schedules');
        $this->addSql('DROP TABLE order_sections');
        $this->addSql('DROP TABLE order_tasks');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE performance_reviews');
        $this->addSql('DROP TABLE planning');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE project_events');
        $this->addSql('DROP TABLE project_health_score');
        $this->addSql('DROP TABLE project_sub_tasks');
        $this->addSql('DROP TABLE project_tasks');
        $this->addSql('DROP TABLE project_technology_versions');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE project_technologies');
        $this->addSql('DROP TABLE running_timers');
        $this->addSql('DROP TABLE saas_distribution_providers');
        $this->addSql('DROP TABLE saas_providers');
        $this->addSql('DROP TABLE saas_services');
        $this->addSql('DROP TABLE saas_subscriptions');
        $this->addSql('DROP TABLE saas_subscriptions_v2');
        $this->addSql('DROP TABLE saas_vendors');
        $this->addSql('DROP TABLE scheduler_entries');
        $this->addSql('DROP TABLE service_categories');
        $this->addSql('DROP TABLE skills');
        $this->addSql('DROP TABLE technologies');
        $this->addSql('DROP TABLE timesheets');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE vacations');
        $this->addSql('DROP TABLE xp_history');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
