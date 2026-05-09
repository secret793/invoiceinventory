<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\SystemSetting;

class SystemSettingController
{
    public function index(Request $req): void
    {
        Response::success(SystemSetting::all('key', 'ASC'));
    }

    public function exchangeRate(Request $req): void
    {
        $row  = \App\Core\Database::queryOne(
            "SELECT value FROM system_settings WHERE key = 'exchange_rate_gmd_usd' LIMIT 1"
        );
        $rate = $row ? (float) $row['value'] : 74.07;
        Response::success(['rate' => $rate, 'currency' => 'GMD/USD']);
    }

    public function update(Request $req): void
    {
        $id   = (int) $req->param('id');
        SystemSetting::findOrFail($id);
        $data = ['value' => $req->input('value')];
        if ($data['value'] === null) Response::error('value is required');
        $row = SystemSetting::update($id, $data);
        Response::success($row, 'Setting updated');
    }
}
