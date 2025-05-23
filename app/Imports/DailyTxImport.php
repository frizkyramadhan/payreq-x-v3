<?php

namespace App\Imports;

use App\Models\DailyTx;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DailyTxImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new DailyTx([
            'create_date' => $this->convertDate($row['create_date']),
            'posting_date' => $this->convertDate($row['posting_date']),
            'duration' => $this->calculateDuration($row['create_date'], $row['posting_date']),
            'vendor_code' => $row['vendor_code'],
            'vendor_name' => $row['vendor_name'],
            'doc_num' => $row['doc_num'],
            'invoice_no' => $row['vendor_ref_no'],
            'doc_type' => $row['doc_type'],
            'project' => $row['project_code'],
            'account' => $row['account_code'],
            'debit' => $row['sys_debit'],
            'credit' => $row['sys_credit'],
            'remarks' => $row['remarks'],
            'user_code' => $row['user_id'],
            'faktur_no' => $row['faktur_no'],
            'faktur_date' => $this->convertDate($row['faktur_date']),
            'will_delete' => $this->calculateDuration($row['create_date'], $row['posting_date']) < 0 ? true : false,
            'uploaded_by' => auth()->user()->id,
        ]);
    }

    private function convertDate($date)
    {
        if ($date) {
            $year = substr($date, 6, 4);
            $month = substr($date, 3, 2);
            $day = substr($date, 0, 2);
            $new_date = $year . '-' . $month . '-' . $day;
            return $new_date;
        } else {
            return null;
        }
    }

    private function calculateDuration($create_date, $posting_date)
    {
        $create_date = new \DateTime($this->convertDate($create_date));
        $posting_date = new \DateTime($this->convertDate($posting_date));

        // Ensure create_date is after or the same as posting_date
        if ($create_date <= $posting_date) {
            return 0;
        }

        $workdays = 0;

        // Iterate through each day between the two dates
        while ($create_date > $posting_date) {
            // Check if the day is a weekday (Monday to Friday)
            if ($create_date->format('N') < 6) {
                $workdays++;
            }
            // Move to the next day
            $create_date->modify('-1 day');
        }

        return $workdays;
    }
}
