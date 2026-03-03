<?php

namespace Cds\NetteModelGenerator;

use Cds\NetteModelGenerator\Data\Enum;
use Cds\NetteModelGenerator\Data\Table;

interface FileManager
{
    // Paths
    public function getEnumPath(Enum $enum): string;

    public function getColumnsPath(Table $table): string;

    public function getActiveRowPath(Table $table): string;

    public function getUserActiveRowPath(Table $table): string;

    public function getManagerPath(): string;

    public function getBaseManagerPath(): string;

    public function getBaseManagerForTablePath(Table $table): string;

    public function getUserManagerForTablePath(Table $table): string;

    public function getExplorerPath(): string;

    // Names
    public function getEnumName(Enum $enum): string;

    public function getColumnsName(Table $table): string;

    public function getActiveRowName(Table $table): string;

    public function getUserActiveRowName(Table $table): string;

    public function getManagerName(): string;

    public function getBaseManagerName(): string;

    public function getBaseManagerForTableName(Table $table): string;

    public function getUserManagerForTableName(Table $table): string;

    public function getActiveRowNamespace(): string;

    public function getExplorerName(): string;
}
