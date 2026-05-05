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
