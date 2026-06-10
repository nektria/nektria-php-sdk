<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Dto\LocalClock;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

readonly class ExcelFile
{
    private Spreadsheet $spreadsheet;

    private function __construct(
        public string $file,
    ) {
        $this->spreadsheet = IOFactory::load($this->file);
    }

    public static function load(string $file): self
    {
        return new self($file);
    }

    public static function new(string $file): self
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $writer->save($file);

        return new self($file);
    }

    public function getCell(string $cell): string
    {
        return (string) $this->spreadsheet->getActiveSheet()->getCell($cell)->getValue();
    }

    public function getCell2(string $column, string | int $row): string
    {
        return $this->getCell($column . $row);
    }

    public function getLocalClock(string $cell, ?string $timezone = null): LocalClock
    {
        $unixTimestamp = (((int) $this->getCell($cell)) - 25569) * 86400;

        return LocalClock::now($timezone)->setTimestamp($unixTimestamp);
    }

    public function getLocalClock2(string $column, string | int $row, ?string $timezone = null): LocalClock
    {
        return $this->getLocalClock($column . $row, $timezone);
    }

    public function save(): void
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->file);
    }

    public function setCell(string $cell, string $value): void
    {
        $this->spreadsheet->getActiveSheet()->setCellValue($cell, $value);
    }

    public function setCell2(string $column, string | int $row, string $value): void
    {
        $this->setCell($column . $row, $value);
    }
}
