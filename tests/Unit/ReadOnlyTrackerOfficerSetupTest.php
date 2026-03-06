<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReadOnlyTrackerOfficerSetupTest extends TestCase
{
    private function projectPath(string $relativePath): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    public function test_read_only_tracker_officer_seeder_defines_expected_role_and_permissions(): void
    {
        $content = file_get_contents($this->projectPath('database/seeders/ReadOnlyTrackerOfficerSeeder.php'));

        $this->assertIsString($content);
        $this->assertStringContainsString("Read Only Tracker Officer", $content);
        $this->assertStringContainsString("'view devices'", $content);
        $this->assertStringContainsString("'view device retrievals'", $content);
        $this->assertStringContainsString("'view confirmed affix'", $content);
        $this->assertStringContainsString("'view_destination_%'", $content);
        $this->assertStringContainsString("'view_allocationpoint_%'", $content);
    }

    public function test_database_seeder_calls_read_only_tracker_officer_seeder(): void
    {
        $content = file_get_contents($this->projectPath('database/seeders/DatabaseSeeder.php'));

        $this->assertIsString($content);
        $this->assertStringContainsString('ReadOnlyTrackerOfficerSeeder::class', $content);
    }

    public function test_admin_panel_provider_contains_dedicated_three_item_navigation_branch(): void
    {
        $content = file_get_contents($this->projectPath('app/Providers/Filament/AdminPanelProvider.php'));

        $this->assertIsString($content);
        $this->assertStringContainsString("hasRole('Read Only Tracker Officer')", $content);
        $this->assertStringContainsString('if (!$isReadOnlyTrackerOfficer)', $content);
        $this->assertStringContainsString("NavigationItem::make('Device Tracker')", $content);
        $this->assertStringContainsString("NavigationItem::make('Device Retrieval')", $content);
        $this->assertStringContainsString("NavigationItem::make('ConfirmedAffix')", $content);
        $this->assertStringContainsString('return $builder;', $content);
    }

    public function test_device_tracker_files_hide_mutating_actions_for_read_only_role(): void
    {
        $listDevicesContent = file_get_contents($this->projectPath('app/Filament/Resources/DeviceResource/Pages/ListDevices.php'));
        $deviceResourceContent = file_get_contents($this->projectPath('app/Filament/Resources/DeviceResource.php'));

        $this->assertIsString($listDevicesContent);
        $this->assertStringContainsString('function isReadOnlyTrackerOfficer(): bool', $listDevicesContent);
        $this->assertStringContainsString('if ($this->isReadOnlyTrackerOfficer()) {', $listDevicesContent);
        $this->assertStringContainsString('return [];', $listDevicesContent);

        $this->assertIsString($deviceResourceContent);
        $this->assertStringContainsString('protected static function isReadOnlyTrackerOfficer(): bool', $deviceResourceContent);
        $this->assertStringContainsString('->actions(static::isReadOnlyTrackerOfficer() ? [] : [', $deviceResourceContent);
        $this->assertStringContainsString('->bulkActions(static::isReadOnlyTrackerOfficer() ? [] : [', $deviceResourceContent);
    }

    public function test_device_retrieval_list_page_enforces_read_only_actions_for_role(): void
    {
        $listDeviceRetrievalsContent = file_get_contents($this->projectPath('app/Filament/Resources/DeviceRetrievalResource/Pages/ListDeviceRetrievals.php'));

        $this->assertIsString($listDeviceRetrievalsContent);
        $this->assertStringContainsString('protected function isReadOnlyTrackerOfficer(): bool', $listDeviceRetrievalsContent);
        $this->assertStringContainsString('if ($this->isReadOnlyTrackerOfficer()) {', $listDeviceRetrievalsContent);
        $this->assertStringContainsString('$deviceRetrievalReportAction,', $listDeviceRetrievalsContent);
        $this->assertStringContainsString('$viewOverstayDevicesAction,', $listDeviceRetrievalsContent);
        $this->assertStringContainsString('->actions($this->isReadOnlyTrackerOfficer() ? [] : [', $listDeviceRetrievalsContent);
        $this->assertStringContainsString('->bulkActions($this->isReadOnlyTrackerOfficer() ? [] : [', $listDeviceRetrievalsContent);
    }

    public function test_device_retrieval_resource_allows_read_only_role_to_view_records(): void
    {
        $resourceContent = file_get_contents($this->projectPath('app/Filament/Resources/DeviceRetrievalResource.php'));

        $this->assertIsString($resourceContent);
        $this->assertStringContainsString("hasRole(['Retrieval Officer', 'Read Only Tracker Officer'])", $resourceContent);
        $this->assertStringContainsString("'Read Only Tracker Officer'", $resourceContent);
    }

    public function test_confirmed_affix_files_enforce_read_only_view_only_behavior(): void
    {
        $resourceContent = file_get_contents($this->projectPath('app/Filament/Resources/ConfirmedAffixedResource.php'));
        $listPageContent = file_get_contents($this->projectPath('app/Filament/Resources/ConfirmedAffixedResource/Pages/ListConfirmedAffixeds.php'));

        $this->assertIsString($resourceContent);
        $this->assertStringContainsString('protected static function isReadOnlyTrackerOfficer(): bool', $resourceContent);
        $this->assertStringContainsString('->actions(static::isReadOnlyTrackerOfficer() ? [] : [', $resourceContent);
        $this->assertStringContainsString('->bulkActions(static::isReadOnlyTrackerOfficer() ? [] : [', $resourceContent);
        $this->assertStringContainsString("'Read Only Tracker Officer'", $resourceContent);
        $this->assertStringContainsString("hasRole(['Retrieval Officer', 'Affixing Officer', 'Read Only Tracker Officer'])", $resourceContent);

        $this->assertIsString($listPageContent);
        $this->assertStringContainsString('protected function isReadOnlyTrackerOfficer(): bool', $listPageContent);
        $this->assertStringContainsString("hasRole(['Retrieval Officer', 'Affixing Officer', 'Read Only Tracker Officer'])", $listPageContent);
        $this->assertStringContainsString('if ($this->isReadOnlyTrackerOfficer()) {', $listPageContent);
        $this->assertStringContainsString('return [];', $listPageContent);
    }

    public function test_visibility_filters_use_inherited_permissions_for_read_only_role(): void
    {
        $deviceRetrievalModelContent = file_get_contents($this->projectPath('app/Models/DeviceRetrieval.php'));
        $deviceRetrievalLogModelContent = file_get_contents($this->projectPath('app/Models/DeviceRetrievalLog.php'));
        $deviceRetrievalResourceContent = file_get_contents($this->projectPath('app/Filament/Resources/DeviceRetrievalResource.php'));
        $confirmedAffixedModelContent = file_get_contents($this->projectPath('app/Models/ConfirmedAffixed.php'));
        $confirmedAffixedResourceContent = file_get_contents($this->projectPath('app/Filament/Resources/ConfirmedAffixedResource.php'));
        $confirmedAffixedListPageContent = file_get_contents($this->projectPath('app/Filament/Resources/ConfirmedAffixedResource/Pages/ListConfirmedAffixeds.php'));

        $this->assertIsString($deviceRetrievalModelContent);
        $this->assertStringContainsString("hasRole(['Retrieval Officer', 'Read Only Tracker Officer'])", $deviceRetrievalModelContent);
        $this->assertStringContainsString('getAllPermissions()', $deviceRetrievalModelContent);

        $this->assertIsString($deviceRetrievalLogModelContent);
        $this->assertStringContainsString("hasRole(['Retrieval Officer', 'Read Only Tracker Officer'])", $deviceRetrievalLogModelContent);
        $this->assertStringContainsString('getAllPermissions()', $deviceRetrievalLogModelContent);

        $this->assertIsString($deviceRetrievalResourceContent);
        $this->assertStringContainsString('getAllPermissions()', $deviceRetrievalResourceContent);

        $this->assertIsString($confirmedAffixedModelContent);
        $this->assertStringContainsString("hasRole(['Retrieval Officer', 'Affixing Officer', 'Read Only Tracker Officer'])", $confirmedAffixedModelContent);
        $this->assertStringContainsString('getAllPermissions()', $confirmedAffixedModelContent);

        $this->assertIsString($confirmedAffixedResourceContent);
        $this->assertStringContainsString('getAllPermissions()', $confirmedAffixedResourceContent);

        $this->assertIsString($confirmedAffixedListPageContent);
        $this->assertStringContainsString('getAllPermissions()', $confirmedAffixedListPageContent);
    }
}
