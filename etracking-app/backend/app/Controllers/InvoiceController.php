<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;

class InvoiceController
{
    public function show(Request $req): void
    {
        Response::success(Invoice::findOrFail((int) $req->param('id')));
    }

    public function generate(Request $req): void
    {
        // Delegated to DeviceRetrievalController::generateInvoice
        (new DeviceRetrievalController())->generateInvoice($req);
    }

    public function pdf(Request $req): void
    {
        $id  = (int) $req->param('id');
        $row = Invoice::findOrFail($id);
        Response::success($row, 'PDF export requires DomPDF. Returning JSON data.');
    }
}
