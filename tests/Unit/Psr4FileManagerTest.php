<?php

namespace Tests\Unit;

use Cds\NetteModelGenerator\Data\Enum;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\Psr4FileManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Psr4FileManager::class)]
class Psr4FileManagerTest extends TestCase
{
    #[Test]
    public function emptyRootDir(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Root directory must not be empty');
        new Psr4FileManager([], []);
    }

    #[Test]
    public function paths(): void
    {
        $fileManager = new Psr4FileManager(['root'], ['App', 'Namespace', 'Test'], false);
        $table = new Table('test_table');
        $enum = new Enum('column_with_enum', ['value1', 'value2']);

        // Generated
        $this->assertSame('root/App/Namespace/Test/Generated/Explorer.php', $fileManager->getExplorerPath());
        $this->assertSame('root/App/Namespace/Test/Generated/Manager.php', $fileManager->getManagerPath());
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Managers/TestTableManagerBase.php',
            $fileManager->getBaseManagerForTablePath($table)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Rows/TestTableActiveRowBase.php',
            $fileManager->getActiveRowPath($table)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Enums/ColumnWithEnum.php',
            $fileManager->getEnumPath($enum)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Columns/TestTable.php',
            $fileManager->getColumnsPath($table)
        );

        // User
        $this->assertSame('root/App/Namespace/Test/Managers/ManagerBase.php', $fileManager->getBaseManagerPath());
        $this->assertSame(
            'root/App/Namespace/Test/Managers/TestTableManager.php',
            $fileManager->getUserManagerForTablePath($table)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Rows/TestTableActiveRow.php',
            $fileManager->getUserActiveRowPath($table)
        );
    }

    /**
     * Tests file paths if the namespace starts with the root directory name.
     */
    #[Test]
    public function pathsNamespaceStartsWithRootDir(): void
    {
        $fileManager = new Psr4FileManager(['root'], ['Root', 'Namespace', 'Test'], false);
        $table = new Table('test_table');
        $enum = new Enum('column_with_enum', ['value1', 'value2']);

        // Generated
        $this->assertSame('root/Namespace/Test/Generated/Explorer.php', $fileManager->getExplorerPath());
        $this->assertSame('root/Namespace/Test/Generated/Manager.php', $fileManager->getManagerPath());
        $this->assertSame(
            'root/Namespace/Test/Generated/Managers/TestTableManagerBase.php',
            $fileManager->getBaseManagerForTablePath($table)
        );
        $this->assertSame(
            'root/Namespace/Test/Generated/Rows/TestTableActiveRowBase.php',
            $fileManager->getActiveRowPath($table)
        );
        $this->assertSame(
            'root/Namespace/Test/Generated/Enums/ColumnWithEnum.php',
            $fileManager->getEnumPath($enum)
        );
        $this->assertSame(
            'root/Namespace/Test/Generated/Columns/TestTable.php',
            $fileManager->getColumnsPath($table)
        );

        // User
        $this->assertSame('root/Namespace/Test/Managers/ManagerBase.php', $fileManager->getBaseManagerPath());
        $this->assertSame('root/Namespace/Test/Managers/TestTableManager.php', $fileManager->getUserManagerForTablePath($table));
        $this->assertSame('root/Namespace/Test/Rows/TestTableActiveRow.php', $fileManager->getUserActiveRowPath($table));
    }

    #[Test]
    public function pathsEmptyNamespace(): void
    {
        $fileManager = new Psr4FileManager(['root'], [], false);
        $table = new Table('test_table');
        $enum = new Enum('column_with_enum', ['value1', 'value2']);

        // Generated
        $this->assertSame('root/Generated/Explorer.php', $fileManager->getExplorerPath());
        $this->assertSame('root/Generated/Manager.php', $fileManager->getManagerPath());
        $this->assertSame('root/Generated/Managers/TestTableManagerBase.php', $fileManager->getBaseManagerForTablePath($table));
        $this->assertSame('root/Generated/Rows/TestTableActiveRowBase.php', $fileManager->getActiveRowPath($table));
        $this->assertSame('root/Generated/Enums/ColumnWithEnum.php', $fileManager->getEnumPath($enum));
        $this->assertSame('root/Generated/Columns/TestTable.php', $fileManager->getColumnsPath($table));

        // User
        $this->assertSame('root/Managers/ManagerBase.php', $fileManager->getBaseManagerPath());
        $this->assertSame('root/Managers/TestTableManager.php', $fileManager->getUserManagerForTablePath($table));
        $this->assertSame('root/Rows/TestTableActiveRow.php', $fileManager->getUserActiveRowPath($table));
    }

    #[Test]
    public function pathsWithSchema(): void
    {
        $fileManager = new Psr4FileManager(['root'], ['App', 'Namespace', 'Test'], true);
        $table = new Table('test_table', 'schema');
        $enum = new Enum('column_with_enum', ['value1', 'value2'], 'schema');

        // Generated
        $this->assertSame('root/App/Namespace/Test/Generated/Explorer.php', $fileManager->getExplorerPath());
        $this->assertSame('root/App/Namespace/Test/Generated/Manager.php', $fileManager->getManagerPath());
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Managers/Schema/TestTableManagerBase.php',
            $fileManager->getBaseManagerForTablePath($table)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Rows/Schema/TestTableActiveRowBase.php',
            $fileManager->getActiveRowPath($table)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Enums/Schema/ColumnWithEnum.php',
            $fileManager->getEnumPath($enum)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Generated/Columns/Schema/TestTable.php',
            $fileManager->getColumnsPath($table)
        );

        // User
        $this->assertSame('root/App/Namespace/Test/Managers/ManagerBase.php', $fileManager->getBaseManagerPath());
        $this->assertSame(
            'root/App/Namespace/Test/Managers/Schema/TestTableManager.php',
            $fileManager->getUserManagerForTablePath($table)
        );
        $this->assertSame(
            'root/App/Namespace/Test/Rows/Schema/TestTableActiveRow.php',
            $fileManager->getUserActiveRowPath($table)
        );
    }

    #[Test]
    public function names(): void
    {
        $fileManager = new Psr4FileManager(['root'], ['App', 'Namespace', 'Test'], false);
        $table = new Table('test_table');
        $enum = new Enum('column_with_enum', ['value1', 'value2']);

        // Generated
        $this->assertSame('App\Namespace\Test\Generated\Explorer', $fileManager->getExplorerName());
        $this->assertSame('App\Namespace\Test\Generated\Manager', $fileManager->getManagerName());
        $this->assertSame(
            'App\Namespace\Test\Generated\Managers\TestTableManagerBase',
            $fileManager->getBaseManagerForTableName($table)
        );
        $this->assertSame(
            'App\Namespace\Test\Generated\Rows\TestTableActiveRowBase',
            $fileManager->getActiveRowName($table)
        );
        $this->assertSame('App\\Namespace\\Test\\Rows', $fileManager->getActiveRowNamespace());
        $this->assertSame(
            'App\Namespace\Test\Generated\Enums\ColumnWithEnum',
            $fileManager->getEnumName($enum)
        );
        $this->assertSame(
            'App\Namespace\Test\Generated\Columns\TestTable',
            $fileManager->getColumnsName($table)
        );

        // User
        $this->assertSame('App\Namespace\Test\Managers\ManagerBase', $fileManager->getBaseManagerName());
        $this->assertSame(
            'App\Namespace\Test\Managers\TestTableManager',
            $fileManager->getUserManagerForTableName($table)
        );
        $this->assertSame(
            'App\Namespace\Test\Rows\TestTableActiveRow',
            $fileManager->getUserActiveRowName($table)
        );
    }

    #[Test]
    public function namesWithSchema(): void
    {
        $fileManager = new Psr4FileManager(['root'], ['App', 'Namespace', 'Test'], true);
        $table = new Table('test_table', 'schema');
        $enum = new Enum('column_with_enum', ['value1', 'value2'], 'schema');

        // Generated
        $this->assertSame('App\Namespace\Test\Generated\Explorer', $fileManager->getExplorerName());
        $this->assertSame('App\Namespace\Test\Generated\Manager', $fileManager->getManagerName());
        $this->assertSame(
            'App\Namespace\Test\Generated\Managers\Schema\TestTableManagerBase',
            $fileManager->getBaseManagerForTableName($table)
        );
        $this->assertSame(
            'App\Namespace\Test\Generated\Rows\Schema\TestTableActiveRowBase',
            $fileManager->getActiveRowName($table)
        );
        // TODO: schemas...
        $this->assertSame('App\\Namespace\\Test\\Rows', $fileManager->getActiveRowNamespace());
        //        $this->assertSame(
        //            'App\Namespace\Test\Generated\Enums\Schema\ColumnWithEnum',
        //            $fileManager->getEnumName($enum)
        //        );
        $this->assertSame(
            'App\Namespace\Test\Generated\Columns\Schema\TestTable',
            $fileManager->getColumnsName($table)
        );

        // User
        $this->assertSame('App\Namespace\Test\Managers\ManagerBase', $fileManager->getBaseManagerName());
        $this->assertSame(
            'App\Namespace\Test\Managers\Schema\TestTableManager',
            $fileManager->getUserManagerForTableName($table)
        );
        $this->assertSame(
            'App\Namespace\Test\Rows\Schema\TestTableActiveRow',
            $fileManager->getUserActiveRowName($table)
        );
    }

    #[Test]
    public function namesWithoutNamespace(): void
    {
        $fileManager = new Psr4FileManager(['root'], [], false);
        $table = new Table('test_table');
        $enum = new Enum('column_with_enum', ['value1', 'value2']);

        // Generated
        $this->assertSame('Generated\Explorer', $fileManager->getExplorerName());
        $this->assertSame('Generated\Manager', $fileManager->getManagerName());
        $this->assertSame(
            'Generated\Managers\TestTableManagerBase',
            $fileManager->getBaseManagerForTableName($table)
        );
        $this->assertSame('Generated\Rows\TestTableActiveRowBase', $fileManager->getActiveRowName($table));
        $this->assertSame('Rows', $fileManager->getActiveRowNamespace());
        $this->assertSame('Generated\Enums\ColumnWithEnum', $fileManager->getEnumName($enum));
        $this->assertSame('Generated\Columns\TestTable', $fileManager->getColumnsName($table));

        // User
        $this->assertSame('Managers\ManagerBase', $fileManager->getBaseManagerName());
        $this->assertSame('Managers\TestTableManager', $fileManager->getUserManagerForTableName($table));
        $this->assertSame('Rows\TestTableActiveRow', $fileManager->getUserActiveRowName($table));
    }
}
