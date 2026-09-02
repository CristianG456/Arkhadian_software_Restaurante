<?php

namespace App\Domain\Production\Printing;

use App\Domain\Production\Models\PrintJob;

final class PrintDocumentRenderer
{
    public function render(PrintJob $job): string
    {
        $data = $job->payload_snapshot;
        $title = match ($job->job_type) {
            PrintJob::TYPE_REPRINT => 'REIMPRESIÓN',
            PrintJob::TYPE_CUSTOMER => 'SOLICITUD DE CLIENTE',
            PrintJob::TYPE_CANCEL => 'CANCELACIÓN',
            PrintJob::TYPE_TRANSFER => 'TRASLADO DE MESA',
            PrintJob::TYPE_PREACCOUNT => "PRECUENTA\nNO ES FACTURA",
            PrintJob::TYPE_TEST => 'PRUEBA DE IMPRESIÓN',
            default => 'COMANDA',
        };
        $lines = [str_repeat('=', 32), $title, str_repeat('=', 32)];
        foreach (['restaurantName', 'location', 'orderLabel', 'waiter', 'createdAt'] as $field) {
            if (! empty($data[$field])) {
                $lines[] = (string) $data[$field];
            }
        }
        $lines[] = str_repeat('-', 32);
        foreach ($data['items'] ?? [] as $item) {
            $lines[] = $item['quantity'].'x '.mb_strtoupper($item['name']);
            if (! empty($item['variant'])) {
                $lines[] = '   '.$item['variant'];
            }
            foreach ($item['modifiers'] ?? [] as $modifier) {
                $lines[] = '   '.$modifier;
            }
            if (! empty($item['notes'])) {
                $lines[] = '   '.$item['notes'];
            }
            if (isset($item['lineTotal'])) {
                $lines[] = '   '.$item['lineTotal'];
            }
        }
        if (isset($data['total'])) {
            $lines[] = str_repeat('-', 32);
            $lines[] = 'TOTAL: '.$data['total'];
        }
        if (! empty($data['reason'])) {
            $lines[] = 'Motivo: '.$data['reason'];
        }
        $lines[] = str_repeat('=', 32);

        return implode("\n", $lines);
    }
}
