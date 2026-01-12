<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use PDO;
use RuntimeException;

class DashboardController
{
    public function __construct(
        private ?PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
    }

    /**
     * Get dashboard summary data
     * Permission: CAMPAIGN_VIEW (or public for residents)
     */
    public function summary(?array $user, array $params = []): array
    {
        // PDO must be valid - if it's null, db_connect.php should have thrown an exception
        if ($this->pdo === null) {
            error_log('DashboardController::summary - PDO is null');
            throw new RuntimeException('Database connection is not available: PDO is null');
        }
        
        if (!($this->pdo instanceof PDO)) {
            error_log('DashboardController::summary - PDO is not a PDO instance, type: ' . gettype($this->pdo));
            throw new RuntimeException('Database connection is not available: PDO is not a valid PDO instance');
        }
        
        // Test the connection before proceeding
        try {
            $this->pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            error_log('DashboardController::summary - PDO connection test failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection test failed: ' . $e->getMessage(), 0, $e);
        }
        
        $userRole = $user ? RoleMiddleware::getUserRole($user, $this->pdo) : null;
        $isResident = $userRole === 'resident' || !$user;

        // Get KPI counts (with individual error handling)
        $kpis = $this->getKPIs($isResident);

        // Get campaign planning snapshot
        $campaignSnapshot = $this->getCampaignSnapshot($isResident);

        // Get event readiness data
        $eventReadiness = $this->getEventReadiness($isResident);

        // Get audience coverage
        $audienceCoverage = $this->getAudienceCoverage($isResident);

        // Get engagement preview
        $engagementPreview = $this->getEngagementPreview($isResident);

        // Get partner snapshot
        $partnerSnapshot = $this->getPartnerSnapshot($isResident);

        // Get content repository snapshot
        $contentSnapshot = $this->getContentSnapshot($isResident);

        // Get alerts and reminders
        $alerts = $this->getAlerts($isResident);

        return [
            'kpis' => $kpis,
            'campaign_snapshot' => $campaignSnapshot,
            'event_readiness' => $eventReadiness,
            'audience_coverage' => $audienceCoverage,
            'engagement_preview' => $engagementPreview,
            'partner_snapshot' => $partnerSnapshot,
            'content_snapshot' => $contentSnapshot,
            'alerts' => $alerts,
        ];
    }

    /**
     * Get KPI summary cards
     */
    private function getKPIs(bool $isResident): array
    {
        $activeCampaigns = 0;
        $scheduledCampaigns = 0;
        $upcomingEvents = 0;
        $definedSegments = 0;
        $partnerOrgs = 0;
        $feedbackResponses = 0;

        try {
            // Active campaigns (scheduled or active status)
            $activeCampaignsQuery = "
                SELECT COUNT(*) 
                FROM campaign_department_campaigns 
                WHERE status IN ('scheduled', 'active')
            ";
            if ($isResident) {
                $activeCampaignsQuery .= " AND status = 'active'";
            }
            $activeCampaigns = (int) $this->pdo->query($activeCampaignsQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting active campaigns: ' . $e->getMessage());
        }

        try {
            // Scheduled campaigns
            $scheduledCampaignsQuery = "
                SELECT COUNT(*) 
                FROM campaign_department_campaigns 
                WHERE status = 'scheduled'
            ";
            $scheduledCampaigns = (int) $this->pdo->query($scheduledCampaignsQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting scheduled campaigns: ' . $e->getMessage());
        }

        try {
            // Upcoming events (next 30 days, planned or ongoing)
            $upcomingEventsQuery = "
                SELECT COUNT(*) 
                FROM events 
                WHERE date >= CURDATE() 
                AND date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND event_status IN ('planned', 'ongoing')
            ";
            $upcomingEvents = (int) $this->pdo->query($upcomingEventsQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting upcoming events: ' . $e->getMessage());
        }

        try {
            // Defined audience segments
            $segmentsQuery = "SELECT COUNT(*) FROM audience_segments";
            $definedSegments = (int) $this->pdo->query($segmentsQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting segments: ' . $e->getMessage());
        }

        try {
            // Partner organizations
            $partnersQuery = "SELECT COUNT(*) FROM partners";
            $partnerOrgs = (int) $this->pdo->query($partnersQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting partners: ' . $e->getMessage());
        }

        // Feedback responses (from surveys)
        $feedbackQuery = "
            SELECT COUNT(DISTINCT survey_id) 
            FROM survey_responses
        ";
        $feedbackResponses = 0;
        try {
            $feedbackResponses = (int) $this->pdo->query($feedbackQuery)->fetchColumn();
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        return [
            'active_campaigns' => $activeCampaigns,
            'scheduled_campaigns' => $scheduledCampaigns,
            'upcoming_events' => $upcomingEvents,
            'defined_segments' => $definedSegments,
            'partner_organizations' => $partnerOrgs,
            'feedback_responses' => $feedbackResponses,
        ];
    }

    /**
     * Get campaign planning snapshot
     */
    private function getCampaignSnapshot(bool $isResident): array
    {
        $byStatus = [];
        $upcoming = [];
        $aiScheduled = 0;
        $manualScheduled = 0;

        try {
            // Campaigns by status
            $statusQuery = "
                SELECT status, COUNT(*) as count
                FROM campaign_department_campaigns
                GROUP BY status
            ";
            $statusResults = $this->pdo->query($statusQuery)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($statusResults as $row) {
                $byStatus[$row['status']] = (int) $row['count'];
            }
        } catch (\Exception $e) {
            error_log('Error getting campaign status: ' . $e->getMessage());
        }

        try {
            // Upcoming campaigns (next 7-14 days)
            $upcomingQuery = "
                SELECT id, title, start_date, status, ai_recommended_datetime
                FROM campaign_department_campaigns
                WHERE start_date >= CURDATE()
                AND start_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                ORDER BY start_date ASC
                LIMIT 10
            ";
            $upcoming = $this->pdo->query($upcomingQuery)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error getting upcoming campaigns: ' . $e->getMessage());
        }

        try {
            // AI vs Manual scheduling
            $aiScheduledQuery = "
                SELECT COUNT(*) 
                FROM campaign_department_campaigns 
                WHERE ai_recommended_datetime IS NOT NULL
            ";
            $aiScheduled = (int) $this->pdo->query($aiScheduledQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting AI scheduled: ' . $e->getMessage());
        }

        try {
            $manualScheduledQuery = "
                SELECT COUNT(*) 
                FROM campaign_department_campaigns 
                WHERE final_schedule_datetime IS NOT NULL 
                AND ai_recommended_datetime IS NULL
            ";
            $manualScheduled = (int) $this->pdo->query($manualScheduledQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting manual scheduled: ' . $e->getMessage());
        }

        return [
            'by_status' => $byStatus,
            'upcoming' => $upcoming,
            'ai_scheduled' => $aiScheduled,
            'manual_scheduled' => $manualScheduled,
        ];
    }

    /**
     * Get event readiness data
     */
    private function getEventReadiness(bool $isResident): array
    {
        $upcoming = [];
        $byType = [];
        $capacityData = [];
        $linkageData = [];

        try {
            // Upcoming events (next 30 days)
            $upcomingQuery = "
                SELECT 
                    e.id as event_id,
                    e.name as event_name,
                    e.event_type,
                    e.date,
                    e.start_time,
                    e.venue,
                    e.capacity,
                    e.event_status,
                    (SELECT COUNT(*) FROM attendance WHERE event_id = e.id) as registered_count
                FROM events e
                WHERE e.date >= CURDATE()
                AND e.date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND e.event_status IN ('planned', 'ongoing')
                ORDER BY e.date ASC, e.start_time ASC
                LIMIT 10
            ";
            $upcoming = $this->pdo->query($upcomingQuery)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error getting upcoming events list: ' . $e->getMessage());
        }

        try {
            // Event types breakdown
            $typesQuery = "
                SELECT event_type, COUNT(*) as count
                FROM events
                WHERE event_status IN ('planned', 'ongoing')
                GROUP BY event_type
            ";
            $typesResults = $this->pdo->query($typesQuery)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($typesResults as $row) {
                $byType[$row['event_type']] = (int) $row['count'];
            }
        } catch (\Exception $e) {
            error_log('Error getting event types: ' . $e->getMessage());
        }

        try {
            // Capacity readiness
            $capacityQuery = "
                SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN capacity IS NOT NULL THEN 1 ELSE 0 END) as events_with_capacity,
                    SUM(CASE WHEN capacity IS NOT NULL AND (SELECT COUNT(*) FROM attendance WHERE event_id = e.id) >= e.capacity THEN 1 ELSE 0 END) as at_capacity
                FROM events e
                WHERE e.event_status IN ('planned', 'ongoing')
            ";
            $capacityData = $this->pdo->query($capacityQuery)->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log('Error getting capacity data: ' . $e->getMessage());
        }

        try {
            // Events linked to campaigns vs standalone
            $linkedQuery = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN linked_campaign_id IS NOT NULL THEN 1 ELSE 0 END) as linked,
                    SUM(CASE WHEN linked_campaign_id IS NULL THEN 1 ELSE 0 END) as standalone
                FROM events
                WHERE event_status IN ('planned', 'ongoing')
            ";
            $linkageData = $this->pdo->query($linkedQuery)->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log('Error getting linkage data: ' . $e->getMessage());
        }

        return [
            'upcoming' => $upcoming,
            'by_type' => $byType,
            'capacity' => $capacityData,
            'linkage' => $linkageData,
        ];
    }

    /**
     * Get audience coverage overview
     */
    private function getAudienceCoverage(bool $isResident): array
    {
        $totalSegments = 0;
        $mostTargeted = [];
        $summary = [];

        try {
            // Total segments
            $totalSegments = (int) $this->pdo->query("SELECT COUNT(*) FROM audience_segments")->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting total segments: ' . $e->getMessage());
        }

        try {
            // Most targeted segments (segments used in most campaigns)
            $targetedQuery = "
                SELECT 
                    s.id,
                    s.segment_name,
                    COUNT(DISTINCT ca.campaign_id) as campaign_count
                FROM audience_segments s
                LEFT JOIN campaign_audience ca ON ca.segment_id = s.id
                GROUP BY s.id, s.segment_name
                ORDER BY campaign_count DESC
                LIMIT 5
            ";
            $mostTargeted = $this->pdo->query($targetedQuery)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error getting most targeted segments: ' . $e->getMessage());
        }

        try {
            // Campaigns per segment summary
            $summaryQuery = "
                SELECT 
                    COUNT(DISTINCT ca.campaign_id) as campaigns_with_segments,
                    COUNT(DISTINCT ca.segment_id) as segments_used
                FROM campaign_audience ca
            ";
            $summary = $this->pdo->query($summaryQuery)->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log('Error getting segment summary: ' . $e->getMessage());
        }

        return [
            'total_segments' => $totalSegments,
            'most_targeted' => $mostTargeted,
            'summary' => $summary,
        ];
    }

    /**
     * Get engagement and impact preview
     */
    private function getEngagementPreview(bool $isResident): array
    {
        $campaignsWithFeedback = 0;
        $eventsWithAttendance = 0;
        $totalAttendance = 0;
        $recentEngagement = 0;

        try {
            // Campaigns with feedback
            $campaignsWithFeedbackQuery = "
                SELECT COUNT(DISTINCT s.campaign_id)
                FROM surveys s
                WHERE EXISTS (
                    SELECT 1 FROM survey_responses sr WHERE sr.survey_id = s.id
                )
            ";
            $campaignsWithFeedback = (int) $this->pdo->query($campaignsWithFeedbackQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting campaigns with feedback: ' . $e->getMessage());
        }

        try {
            // Events with completed attendance
            $eventsWithAttendanceQuery = "
                SELECT COUNT(DISTINCT event_id)
                FROM attendance
            ";
            $eventsWithAttendance = (int) $this->pdo->query($eventsWithAttendanceQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting events with attendance: ' . $e->getMessage());
        }

        try {
            // Total attendance count
            $totalAttendanceQuery = "SELECT COUNT(*) FROM attendance";
            $totalAttendance = (int) $this->pdo->query($totalAttendanceQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting total attendance: ' . $e->getMessage());
        }

        try {
            // Engagement trend (events with attendance in last 30 days)
            $trendQuery = "
                SELECT COUNT(DISTINCT a.event_id)
                FROM attendance a
                JOIN events e ON e.id = a.event_id
                WHERE a.checkin_timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            $recentEngagement = (int) $this->pdo->query($trendQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting recent engagement: ' . $e->getMessage());
        }

        return [
            'campaigns_with_feedback' => $campaignsWithFeedback,
            'events_with_attendance' => $eventsWithAttendance,
            'total_attendance' => $totalAttendance,
            'recent_engagement' => $recentEngagement,
        ];
    }

    /**
     * Get partner and collaboration snapshot
     */
    private function getPartnerSnapshot(bool $isResident): array
    {
        $activePartners = 0;
        $partneredEvents = [];
        $schoolsCount = 0;
        $ngosCount = 0;

        try {
            // Active partners
            $activePartnersQuery = "
                SELECT COUNT(DISTINCT p.id)
                FROM partners p
                WHERE EXISTS (
                    SELECT 1 FROM events e WHERE e.linked_campaign_id IS NOT NULL LIMIT 1
                )
            ";
            $activePartners = (int) $this->pdo->query($activePartnersQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting active partners: ' . $e->getMessage());
        }

        try {
            // Upcoming partnered events (using linked_campaign_id as proxy for partnership)
            $partneredEventsQuery = "
                SELECT 
                    e.id as event_id,
                    e.name as event_name,
                    e.date,
                    0 as partner_count
                FROM events e
                WHERE e.date >= CURDATE()
                AND e.event_status IN ('planned', 'ongoing')
                AND e.linked_campaign_id IS NOT NULL
                ORDER BY e.date ASC
                LIMIT 5
            ";
            $partneredEvents = $this->pdo->query($partneredEventsQuery)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error getting partnered events: ' . $e->getMessage());
        }

        try {
            // Schools and NGOs count
            $schoolsQuery = "
                SELECT COUNT(*) 
                FROM partners 
                WHERE organization_type = 'school'
            ";
            $schoolsCount = (int) $this->pdo->query($schoolsQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting schools count: ' . $e->getMessage());
        }

        try {
            $ngosQuery = "
                SELECT COUNT(*) 
                FROM partners 
                WHERE organization_type = 'ngo'
            ";
            $ngosCount = (int) $this->pdo->query($ngosQuery)->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting NGOs count: ' . $e->getMessage());
        }

        return [
            'active_partners' => $activePartners,
            'upcoming_partnered_events' => $partneredEvents,
            'schools_count' => $schoolsCount,
            'ngos_count' => $ngosCount,
        ];
    }

    /**
     * Get content repository snapshot
     */
    private function getContentSnapshot(bool $isResident): array
    {
        $totalContent = 0;
        $approvedContent = 0;
        $pendingContent = 0;
        $draftContent = 0;
        $recentContent = [];
        $byType = [];
        $byCategory = [];

        try {
            // Total content items
            $totalContent = (int) $this->pdo->query("SELECT COUNT(*) FROM content_items")->fetchColumn();
        } catch (\Exception $e) {
            error_log('Error getting total content: ' . $e->getMessage());
        }

        try {
            // Content by approval status
            $statusQuery = "
                SELECT approval_status, COUNT(*) as count
                FROM content_items
                GROUP BY approval_status
            ";
            $statusResults = $this->pdo->query($statusQuery)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($statusResults as $row) {
                switch ($row['approval_status']) {
                    case 'approved':
                        $approvedContent = (int) $row['count'];
                        break;
                    case 'pending':
                        $pendingContent = (int) $row['count'];
                        break;
                    case 'draft':
                        $draftContent = (int) $row['count'];
                        break;
                }
            }
        } catch (\Exception $e) {
            error_log('Error getting content by status: ' . $e->getMessage());
        }

        try {
            // Recent approved content (last 5)
            $recentQuery = "
                SELECT id, title, content_type, hazard_category, approval_status
                FROM content_items
                WHERE approval_status = 'approved'
                ORDER BY updated_at DESC, created_at DESC
                LIMIT 5
            ";
            $recentContent = $this->pdo->query($recentQuery)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error getting recent content: ' . $e->getMessage());
        }

        try {
            // Content by type
            $typeQuery = "
                SELECT content_type, COUNT(*) as count
                FROM content_items
                WHERE approval_status = 'approved'
                GROUP BY content_type
            ";
            $typeResults = $this->pdo->query($typeQuery)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($typeResults as $row) {
                $byType[$row['content_type']] = (int) $row['count'];
            }
        } catch (\Exception $e) {
            error_log('Error getting content by type: ' . $e->getMessage());
        }

        try {
            // Content by hazard category
            $categoryQuery = "
                SELECT hazard_category, COUNT(*) as count
                FROM content_items
                WHERE approval_status = 'approved'
                AND hazard_category IS NOT NULL
                GROUP BY hazard_category
                ORDER BY count DESC
                LIMIT 5
            ";
            $categoryResults = $this->pdo->query($categoryQuery)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($categoryResults as $row) {
                $byCategory[$row['hazard_category']] = (int) $row['count'];
            }
        } catch (\Exception $e) {
            error_log('Error getting content by category: ' . $e->getMessage());
        }

        return [
            'total_content' => $totalContent,
            'approved_content' => $approvedContent,
            'pending_content' => $pendingContent,
            'draft_content' => $draftContent,
            'recent_content' => $recentContent,
            'by_type' => $byType,
            'by_category' => $byCategory,
        ];
    }

    /**
     * Get alerts and reminders
     */
    private function getAlerts(bool $isResident): array
    {
        $alerts = [];

        try {
            // Campaigns missing schedules
            $missingScheduleQuery = "
                SELECT id, title, status, owner_id as created_by
                FROM campaign_department_campaigns
                WHERE status IN ('draft', 'scheduled')
                AND final_schedule_datetime IS NULL
                AND ai_recommended_datetime IS NULL
                LIMIT 5
            ";
            $missingSchedule = $this->pdo->query($missingScheduleQuery)->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($missingSchedule)) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Campaigns Missing Schedules',
                    'count' => count($missingSchedule),
                    'items' => $missingSchedule,
                ];
                
                // Create notifications for campaign creators
                foreach ($missingSchedule as $campaign) {
                    if ($campaign['created_by']) {
                        try {
                            \App\Controllers\NotificationController::create(
                                $this->pdo,
                                (int) $campaign['created_by'],
                                'reminder',
                                'Campaign Missing Schedule',
                                "Campaign '{$campaign['title']}' is missing a schedule. Please set a schedule to proceed.",
                                '/public/campaigns.php#list-section',
                                'fas fa-clock'
                            );
                        } catch (\Exception $e) {
                            error_log('Failed to create notification: ' . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Error getting missing schedule alerts: ' . $e->getMessage());
        }

        try {
            // Events happening within 72 hours
            $upcomingEventsQuery = "
                SELECT id, name as event_name, date, start_time, venue
                FROM events
                WHERE date >= CURDATE()
                AND date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                AND event_status IN ('planned', 'ongoing')
                ORDER BY date ASC, start_time ASC
                LIMIT 5
            ";
            $upcomingEvents = $this->pdo->query($upcomingEventsQuery)->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($upcomingEvents)) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Events in Next 72 Hours',
                    'count' => count($upcomingEvents),
                    'items' => $upcomingEvents,
                ];
                
                // Note: created_by column doesn't exist in events table, skip notification creation
            }
        } catch (\Exception $e) {
            error_log('Error getting upcoming events alerts: ' . $e->getMessage());
        }

        try {
            // Campaigns without assigned audience segments
            $noSegmentsQuery = "
                SELECT c.id, c.title, c.status
                FROM campaign_department_campaigns c
                LEFT JOIN campaign_audience ca ON ca.campaign_id = c.id
                WHERE ca.campaign_id IS NULL
                AND c.status IN ('draft', 'scheduled', 'active')
                LIMIT 5
            ";
            $noSegments = $this->pdo->query($noSegmentsQuery)->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($noSegments)) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Campaigns Without Audience Segments',
                    'count' => count($noSegments),
                    'items' => $noSegments,
                ];
            }
        } catch (\Exception $e) {
            error_log('Error getting no segments alerts: ' . $e->getMessage());
        }

        try {
            // Events not linked to any campaign
            $standaloneEventsQuery = "
                SELECT id, name as event_name, date, event_status
                FROM events
                WHERE linked_campaign_id IS NULL
                AND event_status IN ('planned', 'ongoing')
                LIMIT 5
            ";
            $standaloneEvents = $this->pdo->query($standaloneEventsQuery)->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($standaloneEvents)) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Standalone Events (Not Linked to Campaigns)',
                    'count' => count($standaloneEvents),
                    'items' => $standaloneEvents,
                ];
            }
        } catch (\Exception $e) {
            error_log('Error getting standalone events alerts: ' . $e->getMessage());
        }

        return $alerts;
    }

    /**
     * Global search (campaigns, events, content titles)
     */
    public function search(?array $user, array $params = []): array
    {
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            return ['data' => []];
        }

        $results = [];

        // Search campaigns
        $campaignsQuery = "
            SELECT 'campaign' as type, id, title as name, 'campaigns.php' as url
            FROM campaign_department_campaigns
            WHERE title LIKE :query
            LIMIT 5
        ";
        $stmt = $this->pdo->prepare($campaignsQuery);
        $stmt->execute(['query' => '%' . $query . '%']);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $campaigns);

        // Search events
        $eventsQuery = "
            SELECT 'event' as type, id, name, 'events.php' as url
            FROM events
            WHERE name LIKE :query
            LIMIT 5
        ";
        $stmt = $this->pdo->prepare($eventsQuery);
        $stmt->execute(['query' => '%' . $query . '%']);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $events);

        // Search content
        $contentQuery = "
            SELECT 'content' as type, id, title as name, 'content.php' as url
            FROM content_items
            WHERE title LIKE :query
            AND approval_status = 'approved'
            LIMIT 5
        ";
        $stmt = $this->pdo->prepare($contentQuery);
        $stmt->execute(['query' => '%' . $query . '%']);
        $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $content);

        return ['data' => array_slice($results, 0, 10)];
    }
}

