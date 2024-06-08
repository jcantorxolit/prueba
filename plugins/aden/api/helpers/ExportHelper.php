<?php

namespace AdeN\Api\Helpers;

use AdeN\Api\Classes\ExcelSheet;
use AdeN\Api\Classes\SnappyPdfOptions;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Carbon\Carbon;
use Excel;
use Log;
use Monolog\Logger;
use October\Rain\Support\Collection;
use PHPExcel_Cell;
use Psr\Log\NullLogger;

/**
 * Parse and build sql expressions
 */
class ExportHelper
{
    public static function excelMultipleSheets($filename, ExcelSheet $excelSheet, $format = 'xlsx', $headerInfo = [])
    {
        Excel::create($filename, function ($excel) use ($excelSheet, $headerInfo) {
            // Call them separately
            //$excel->setDescription($sheetName);

            $sheets = $excelSheet->getSheets();

            if (count($sheets) > 0) {
                $excel->setDescription($excelSheet->getSheetAtIndex(0)->name);
            }

            foreach ($sheets as $key => $sheetItem) {
                $data = $sheetItem->data;
                $columFormats = $sheetItem->columFormats;

                $excel->sheet($sheetItem->name, function ($sheet) use ($data, $columFormats, $headerInfo) {
                    if ($columFormats && count($columFormats)) {
                        $sheet->setColumnFormat($columFormats);
                    }

                    $rowStyles = 1;
                    $freeze = 2;
                    if (count($headerInfo)) {
                        $sheet->fromArray([$headerInfo]);
                        $sheet->fromArray($data);
                        $rowStyles = 3;
                        $freeze = 4;
                    } else {
                        $sheet->fromArray($data, null, 'A1', true, true);
                    }

                    $sheet->row($rowStyles, function ($row) {
                        $row->setBackground('#958057');
                        $row->setFontColor('#FFFFFF');
                        $row->setAlignment('center');
                        $row->setValignment('center');
                        $row->setFont(array(
                            'family' => 'Calibri',
                            'size' => '13',
                            'bold' => true,
                        ));
                    });

                    $t = count(current($data)) - 1;
                    $filterColumns = PHPExcel_Cell::stringFromColumnIndex($t);
                    $sheet->setFreeze("A{$freeze}");
                    $sheet->setAutoFilter("A{$rowStyles}:{$filterColumns}{$t}");
                    $sheet->setHeight(1, 20);
                });
            }
        })->export($format);
    }

    public static function excel($filename, $sheetName, $data, $format = 'xlsx', $headerInfo = [])
    {
        self::excelMultipleSheets($filename, (new ExcelSheet)->addSheet($sheetName, $data), $format, $headerInfo);
    }

    public static function excelStorage($filename, $sheetName, $data, $format = 'xlsx')
    {
        Excel::create($filename, function ($excel) use ($sheetName, $data) {
            // Call them separately
            $excel->setDescription($sheetName);

            $excel->sheet($sheetName, function ($sheet) use ($data) {

                $sheet->fromArray($data, null, 'A1', true, true);

                $sheet->row(1, function ($row) {
                    $row->setBackground('#958057');
                    $row->setFontColor('#FFFFFF');
                    $row->setAlignment('center');
                    $row->setValignment('center');
                    $row->setFont(array(
                        'family' => 'Calibri',
                        'size' => '13',
                        'bold' => true,
                    ));
                });

                $sheet->setFreeze('A2');
                $sheet->setAutoFilter();

                $sheet->setHeight(1, 20);
            });
        })->store($format, CmsHelper::getStorageDirectory('excel/exports'));
    }

    public static function zip($filename, $data)
    {
        try {
            $zip =  new \ZipArchive();

            if ($zip->open($filename,  \ZipArchive::CREATE) !== true) {
                throw new \Exception("Could not open archive", 403);
            }

            foreach ($data as $file) {
                $zip->addFile($file['fullPath'], $file['filename']);
            }

            $zip->close();
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public static function zipFileSystemStream($filename, $data)
    {
        try {
            $zip =  new \ZipArchive();

            if ($zip->open($filename,  \ZipArchive::CREATE) !== true) {
                throw new \Exception("Could not open archive", 403);
            }

            foreach ($data as $file) {
                if (array_key_exists('fileContents', $file)) {
                    $zip->addFromString($file['filename'], $file['fileContents']->getContent());
                } else {
                    $zip->addFile($file['fullPath'], $file['filename']);
                }
            }

            $zip->close();
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public static function zipDownload($filename, $data)
    {
        try {
            $zip =  new \ZipArchive();

            $tmp_file = tempnam('.', '');

            if ($zip->open($tmp_file,  \ZipArchive::CREATE) !== true) {
                throw new \Exception("Could not open archive", 403);
            }

            foreach ($data as $file) {
                if (isset($file['isDir']) && $file['isDir']) {
                    $zip->addEmptyDir($file['name']);
                    foreach ($file['items'] as $item) {
                        if (filter_var($item['fullPath'], FILTER_VALIDATE_URL)) {
                            $contents = CurlHelper::downloadFileFromUrl($item['fullPath']);
                            $zip->addFromString($file['name'] . '/' . basename($item['filename']), $contents);
                        } else if (isset($item['fileContents'])) {
                            $zip->addFromString($file['name'] . '/' . basename($item['filename']), $item['fileContents']);
                        } else {
                            $zip->addFile($item['fullPath'], $file['name'] . '/' .  $item['filename']);
                        }
                    }
                } else {
                    if (filter_var($file['fullPath'], FILTER_VALIDATE_URL)) {
                        $contents = CurlHelper::downloadFileFromUrl($file['fullPath']);
                        $zip->addFromString(basename($file['filename']), $contents);
                    } else if (isset($item['fileContents'])) {
                        $zip->addFromString(basename($file['filename']), $file['fileContents']);
                    } else {
                        $zip->addFile($file['fullPath'], $file['filename']);
                    }
                }
            }

            $zip->close();

            header('Content-disposition: attachment; filename="' . $filename .  '"');
            header("Content-Transfer-Encoding: binary");
            header('Content-type: application/zip');
            readfile($tmp_file);
            unlink($tmp_file);
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public static function pdf($view, $data, $filename, SnappyPdfOptions $pdfOptions)
    {
        return self::getSnappyPdf($view, $data, $pdfOptions)
            ->download($filename);
    }

    public static function stream($view, $data, SnappyPdfOptions $pdfOptions)
    {
        return self::getSnappyPdf($view, $data, $pdfOptions)
            ->stream();
    }

    public static function store($view, $data, $path, SnappyPdfOptions $pdfOptions)
    {
        self::getSnappyPdf($view, $data, $pdfOptions)
            ->save($path);
    }

    private static function getSnappyPdf($view, $data, SnappyPdfOptions $pdfOptions)
    {
        $pdfView = SnappyPdf::loadView($view, $data)
            ->setPaper($pdfOptions->pager)
            ->setOrientation($pdfOptions->orientation)
            ->setWarnings($pdfOptions->warnings);

        //$pdfView->setLogger(Log::getMonolog());

        foreach ($pdfOptions->toArray() as $key => $value) {
            $pdfView->setOption($key, $value);
        }

        return $pdfView;
    }

    public static function headings($data, $heading)
    {
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        if (!count($data)) {
            $item = [];
            foreach ($heading as $key => $value) {
                $item[$key] = null;
            }
            return [$item];
        }

        return array_map(function ($row) use ($heading) {
            $item = [];
            foreach ($heading as $key => $value) {
                if (is_object($row)) {
                    $item[$key] = $row->{$value};
                } else if (is_array($row)) {
                    $item[$key] = $row[$value];
                } else {
                    $item[$key] = null;
                }

                if (ExportHelper::isDate($item[$key])) {
                    $item[$key] = Carbon::parse($item[$key])->format('d/m/Y');
                }
            }

            return $item;
        }, $data);
    }

    public static function fileIterator($data, $fullPathFiled, $filenameField)
    {
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        return array_map(function ($row) use ($fullPathFiled, $filenameField) {
            return [
                "fullPath" => $row->{$fullPathFiled},
                "filename" => $row->{$filenameField},
            ];
        }, array_filter($data, function ($row) use ($fullPathFiled) {
            $filename = $row->{$fullPathFiled};
            return $filename; // && file_exists ( $filename );
        }));
    }

    private static function isDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function configSheetValidation(array $cells, $sheet)
    {
        foreach ($cells as $cell => $info) {
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Error de entrada');
            $validation->setError('El valor no está en la lista.');
            $validation->setFormula1($info['formula']);
            $sheet->setDataValidation($info['range'], $validation);
        }
    }
}
