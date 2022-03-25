<?php

use App\Reporter;
use PHPUnit\Framework\TestCase;

class ReporterTest extends TestCase {
    public function testGetStudent() {
        $testStudents = [["id" => 1, "name" => "tester"]];
        $reporter = new Reporter($testStudents, [], []);
        $student = $reporter->getStudent("1");
        $this->assertEquals("tester", $student["name"]);
    }

    public function testGetNoneExistentStudent() {
        $testStudents = [["id" => 1, "name" => "tester"]];
        $reporter = new Reporter($testStudents, [], []);
        $student = $reporter->getStudent("2");
        $this->assertEmpty($student);
    }
}
