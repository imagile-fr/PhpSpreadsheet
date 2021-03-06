<?php

namespace PhpOffice\PhpSpreadsheetTests\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PHPUnit\Framework\TestCase;

class UnparsedDataCloneTest extends TestCase
{
    /**
     * Test load and save Xlsx file with unparsed data (form elements, protected sheets, alternate contents, printer settings,..).
     */
    public function testLoadSaveXlsxWithUnparsedDataClone(): void
    {
        $sampleFilename = 'tests/data/Writer/XLSX/drawing_on_2nd_page.xlsx';
        $resultFilename = tempnam(File::sysGetTempDir(), 'phpspreadsheet-test');
        Settings::setLibXmlLoaderOptions(null); // reset to default options
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($sampleFilename);
        $spreadsheet->setActiveSheetIndex(1);
        $sheet = $spreadsheet->getActiveSheet();
        $drawings = $sheet->getDrawingCollection();
        self::assertCount(1, $drawings);
        $sheetCodeName = $sheet->getCodeName();
        $unparsedLoadedData = $spreadsheet->getUnparsedLoadedData();
        self::assertArrayHasKey('printerSettings', $unparsedLoadedData['sheets'][$sheetCodeName]);
        self::assertCount(1, $unparsedLoadedData['sheets'][$sheetCodeName]['printerSettings']);

        $clonedSheet = clone $spreadsheet->getActiveSheet();
        $clonedSheet->setTitle('Clone');
        $spreadsheet->addSheet($clonedSheet);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($resultFilename);
        $dupname = 'Unable to open saved file';
        $zip = zip_open($resultFilename);
        if (is_resource($zip)) {
            $names = [];
            $dupname = '';
            while ($zip_entry = zip_read($zip)) {
                $zipname = zip_entry_name($zip_entry);
                if (in_array($zipname, $names)) {
                    $dupname .= "$zipname,";
                } else {
                    $names[] = $zipname;
                }
            }
            zip_close($zip);
        }
        unlink($resultFilename);
        self::assertEquals('', $dupname);
    }

    /**
     * Test that saving twice with same writer works.
     */
    public function testSaveTwice(): void
    {
        $sampleFilename = 'tests/data/Writer/XLSX/drawing_on_2nd_page.xlsx';
        $resultFilename1 = tempnam(File::sysGetTempDir(), 'phpspreadsheet-test1');
        $resultFilename2 = tempnam(File::sysGetTempDir(), 'phpspreadsheet-test2');
        self::assertNotEquals($resultFilename1, $resultFilename2);
        Settings::setLibXmlLoaderOptions(null); // reset to default options
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($sampleFilename);
        $sheet = $spreadsheet->setActiveSheetIndex(1);
        $sheet->setTitle('Original');

        $clonedSheet = clone $spreadsheet->getActiveSheet();
        $clonedSheet->setTitle('Clone');
        $spreadsheet->addSheet($clonedSheet);
        $clonedSheet->getCell('A8')->setValue('cloned');
        $sheet->getCell('A8')->setValue('original');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($resultFilename1);
        $reader1 = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet1 = $reader1->load($resultFilename1);
        unlink($resultFilename1);
        $sheet1c = $spreadsheet1->getSheetByName('Clone');
        $sheet1o = $spreadsheet1->getSheetByName('Original');

        $writer->save($resultFilename2);
        $reader2 = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet2 = $reader2->load($resultFilename2);
        unlink($resultFilename2);
        $sheet2c = $spreadsheet2->getSheetByName('Clone');
        $sheet2o = $spreadsheet2->getSheetByName('Original');

        self::assertEquals($spreadsheet1->getSheetCount(), $spreadsheet2->getSheetCount());
        self::assertCount(1, $sheet1c->getDrawingCollection());
        self::assertCount(1, $sheet1o->getDrawingCollection());
        self::assertCount(1, $sheet2c->getDrawingCollection());
        self::assertCount(1, $sheet2o->getDrawingCollection());
        self::assertEquals('original', $sheet1o->getCell('A8')->getValue());
        self::assertEquals('original', $sheet2o->getCell('A8')->getValue());
        self::assertEquals('cloned', $sheet1c->getCell('A8')->getValue());
        self::assertEquals('cloned', $sheet2c->getCell('A8')->getValue());
    }
}
