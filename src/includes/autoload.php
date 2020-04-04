<?php

require_once "../vendor/autoload.php";


spl_autoload_register(function ($className) {
    $file = dirname(__DIR__) . "\\..\\". $className . '.php';
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
    if (file_exists($file)) {
        include $file;
    }
});


/**
 * Validates data by sanitizing it then checking it respects given predicate
 * @param $data string: data transmitted in the request
 * @param &$var string (modified): reference to the variable in which the result will be placed on success
 * @param &$err_var string (modified): reference to the error variable to be modified in case of error
 * @param $name: name of data, e.g. label name
 * @param $predicate callable: predicate function that returns true on valid data
 * @param $sanitize_further boolean: whether slashes and html chars should be stripped from data
 * @return bool whether the data is valid or not
 */
function test_input($data, &$var, &$err_var, $name, $predicate, $sanitize_further = true)
{
    if (empty($data)) {
        $err_var = $name . " is required";
    } else {
        $data = trim($data);
        if ($sanitize_further) {
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
        }

        if ($predicate($data)) {
            $var = $data;
            return true;
        } else {
            $err_var = "Invalid Format for " . $name . ": " . $data;
        }
    }

    return false;
}

/**
 * @param $rows array: an array of arrays, where each sub-array is a row with its column values as elements
 * @return string: a formatted html table containing those elements
 */
function buildTable($rows)
{
    $table = "<table>";
    foreach ($rows as $row) {
        $table .= "<tr>";
        foreach ($row as $column) {
            $table .= "<th>" . $column . "</th>";
        }
        $table .= "</tr>";
    }
    $table .= "</table>";
    return $table;
}

function buildFormButton($text, $payload_name, $payload, $class)
{
    $self = htmlspecialchars($_SERVER["PHP_SELF"]);

    $res = "<form method='post' action='$self'>";
    $res .= "<input type='hidden' name='$payload_name' value='$payload' />";
    $res .= "<button class='button button-submit $class' type='submit'>$text</button>";
    $res .= "</form>";

    return $res;
}

function buildCheckboxForGroup($group, $value, $class)
{
    $group .= "[]";
    return "<input type='checkbox' class='checkbox $class' name='$group' value='$value' />";
}
