<?php

namespace Hoogi91\Spreadsheets\Tests\Unit;

use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;

trait DsnProviderTrait
{

    public function legacyProvider(): array
    {
        return [
            'unknown file' => ['', InvalidDataSourceNameException::class],
            'with invalid file identifier' => [
                'file:0unknwon|1!D2:G5!vertical',
                InvalidDataSourceNameException::class
            ],
            'with invalid file reference' => [
                'file:0|1!D2:G5!vertical',
                InvalidDataSourceNameException::class
            ],
            'with invalid sheet index' => [
                'file:5|-1!D2:G5!vertical',
                InvalidDataSourceNameException::class
            ],
            'without file prefix' => [
                '5|1!D2:G5!vertical',
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical'
            ],
            'without direction' => [
                'file:5|2!A2:B5',
                'spreadsheet://5?index=2&range=A2%3AB5'
            ],
            'valid dsn' => [
                'file:5|1!D2:G5!vertical',
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical'
            ],
        ];
    }

    public function dsnProvider(): array
    {
        return [
            'unknown file' => ['', InvalidDataSourceNameException::class],
            'without file prefix' => [
                '5?index=1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class
            ],
            'with invalid file identifier' => [
                'spreadsheet://0unknown?index=1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class
            ],
            'with invalid file reference' => [
                'spreadsheet://0?index=1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class
            ],
            'with invalid sheet index' => [
                'spreadsheet://5?index=-1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class
            ],
            'without direction' => [
                'spreadsheet://5?index=2&range=A2%3AB5',
                'spreadsheet://5?index=2&range=A2%3AB5'
            ],
            'valid dsn' => [
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical',
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical'
            ],
        ];
    }
}
