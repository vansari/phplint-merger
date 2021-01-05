# phplint-merger
Simple XML Merger for overtrue/phplint xml reports

## Description

This package is inspired from the Repository [PHPUnit-Merger](https://github.com/Nimut/phpunit-merger).  
The merger can be used to combine many xml results from the [phplint](https://github.com/overtrue/phplint) Repo.

We are using the overtrue/phplint package for static analyze.
overtrue/phplint is a fast tool with flexible configuration via yaml. It generates one testsuite (PHP Linter) with a big testcase with all errors.
But sometimes it is not necessary to analyze all files again and again.

For us it was necessary to lint only files which are new or modified by the developer.
In our CI we run the lint against this files.

````shell
vendor/bin/phplint ./this/is/new/File.php -c .phplint.xml --xml=build/phplint/lint-result-File.xml
````

If you try to merge this file with another lint result file it will be not possible.

Here is the benfit of this new package:

The script sorts the results from one testcase with many classes to one testcase per class with the related errors.  
The testsuite will have an overall result of the tests and errors.

The testcases will have an overall result of the errors.

## Install
````
composer install vansari/phplint-merger
````

### Example
File 1
```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="PHP Linter" timestamp="2020-12-10T20:35:50+0100" time="1 sec" tests="1" errors="2">
        <testcase errors="2" failures="0">
            <error type="Error" message=" Methods with the same name as their class will not be constructors in line 3">/root/path/of/project/src/folder/subfolder1/Class1.php</error>
            <error type="Error" message=" Unparenthesized `a ? b : c ? d : e` is deprecated. Use either `(a ? b : c) ? d : e` or `a ? b : (c ? d : e)` in line 140">/root/path/of/project/src/folder/subfolder1/Class1.php</error>
        </testcase>
    </testsuite>
</testsuites>
```

File 2
```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="PHP Linter" timestamp="2020-12-10T20:35:50+0100" time="1 sec" tests="1" errors="2">
        <testcase errors="2" failures="0">
            <error type="Error" message=" Array and string offset access syntax with curly braces is deprecated in line 91">/root/path/of/project/src/folder/subfolder1/Class2.php</error>
            <error type="Error" message=" Array and string offset access syntax with curly braces is deprecated in line 8">/root/path/of/project/src/folder/subfolder1/Class2.php</error>
        </testcase>
    </testsuite>
</testsuites>
```
Execute XML Merge
```shell
vendor/bin/phplint-merger xml path/to/results path/to/output.xml
```

Result:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="PHP Linter" tests="2" errors="4">
        <testcase name="/root/path/of/project/src/folder/subfolder1/Class1.php" errors="2" failures="0">
            <error type="Error" message="Methods with the same name as their class will not be constructors in line 3"/>
            <error type="Error" message="Unparenthesized `a ? b : c ? d : e` is deprecated. Use either `(a ? b : c) ? d : e` or `a ? b : (c ? d : e)` in line 140"/>
        </testcase>
        <testcase name="/root/path/of/project/src/folder/subfolder1/Class2.php" errors="2" failures="0">
            <error type="Error" message="Array and string offset access syntax with curly braces is deprecated in line 91"/>
            <error type="Error" message="Array and string offset access syntax with curly braces is deprecated in line 8"/>
        </testcase>
    </testsuite>
</testsuites>

```