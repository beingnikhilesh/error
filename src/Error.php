<?php

namespace beingnikhilesh\error;

/*
 * Library to hold the error details or the success status
 * 
 * Dependencies
 *  Set the self::DETAILED_ERROR constant
 * 
 * Version 
 * v0.0.7
 * 
 * Changes
 *  v0.0.2
 *      The self::check_error() function now return FALSE if success is set and hence if a tasked has been already finished before executing its helpfull.
 *      New function appended get_success_data() which returns the success data.
 *      The self::get_returndata() Now returns warnings(Class 2 message) appended to the message.
 * 
 *  v0.0.3
 *      Now, The library does not throw error if self::DETAILED_ERROR constant is not set
 * 
 *  v0.0.4
 *      Now we can even store the process log trail in the file
 *  v0.0.5
 *      A lot of changes have been Made
 *      Error Messages are now separated Error Groups
 *  v0.0.6
 *      Fixed a small bug which caused error to be set to 1 even if successmessage is set to TRUE
 *      Changes the way returndata function works
 *  v0.0.7
 *      Fixed a Small Error in error_lib::get_returndata function
 * 
 */

class Error {

    //The variable to hold the error data
    private static $error = [];
    //The boolean variable to check if error exists
    private static $error_set = 0;
    //Set the status
    private static $status = 1;
    //Set the success status
    private static $success_message = '';
    //Set the success data
    private static $success_data;
    //Set the success data indicator
    private static $success_data_set = 0;

    //Check if Detailed Error with Classes Backtrace is Expected
    const DETAILED_ERROR = 0;

    function __construct() {
        self::$error = [];
    }

    /*
     *  Function to call the error message placeholder
     * 
     *  @param      $error_message      String to set as error message
     *  @param      $error_group        Name of the Group where the Error is to be Put
     *                                  Default: General
     */

    public static function set_error($error_message, $error_group = 'general') {
        //Validate the input
        if ($error_message == '') {
            self::set_error_message('Invalid Error Input Provided');
        }

        //Set the actual Error
        self::set_error_message($error_message, $error_group);
    }

    /*
     * Public Function to clear all the error in the library
     */

    public static function clear_errors() {
        //Set the error variable
        self::$error_set = 0;
        //Change the status variable
        self::$status = 1;
        self::$error = [];
    }

    /*
     *  Function to call the debug message placeholder
     * 
     *  @param      $error_message      String to set as debug message
     */

    public static function debug($error_message) {
        //Validate the input
        if ($error_message == '') {
            self::set_error('Invalid Debug Input Provided');
        }

        //Set the actual Error
        self::set_error_message($error_message, 'debug');
    }

    /*
     *  The public function to be used to set the success message
     * 
     *  @param     $message     Success Message to be set
     */

    public static function set_successmessage($message) {
        //Verify the input
        if ($message == '') {
            self::set_error('The Message provided is not in the appropriate form.');
        }

        //Set the message
        self::$success_message = $message;
        //Set Successmessage in case, success is set with Errors
        self::set_error($message, 'success_message');
    }

    /*
     *  The public function to be used to set the success message
     * 
     *  @param     $message     Success Data in Obj or Array Format to be set
     */

    public static function set_successdata($message) {
        //Verify the input
        if ($message == '') {
            self::set_error('The Message provided is not in the appropriate form');
        }

        //Set the Success Data/Array/Object indicator
        self::$success_data_set = 1;
        //Set the message
        self::$success_data = $message;
    }

    /*
     *  Function to call the error message placeholder
     * 
     *  @param      $error_message      String to set as error message
     *  @param      $error_group        The Error Group, Default general  
     */

    private static function set_error_message($error_message, $error_group = 'general') {
        //We would not validate the inputs as it is been send by our own method
        /*
         * The actual error is set in the Form
         *      array(
         *          'message' => message
         *          'line_no' => Line no from which the error originated
         *          'method' => Method from which the error originated
         *          'class' => The current class name
         *          'function' => The function generating the message
         *          'time' => date('Y-m-d H:i:s')
         *      )
         *
         */

        //Define the variables
        //The backtrace array  
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        //The count of the array
        $n = count($backtrace);
        //The error data storage
        $error_data = [];
        //error heirarchy variable
        $heirarchy_tree = '';

        //Create the error heirarchy variable
        for ($k = 2; $k < ($n - 2); $k++) {
            $heirarchy_tree = $backtrace[$k]['class'] . "[" . $backtrace[$k]['function'] . "()]" . (($k == 2) ? '' : '->') . $heirarchy_tree;
        }


        //Create the array
        $error_data = array(
            'message' => $error_message,
            'line_no' => $backtrace[$n - 4]['line'],
            'method' => $backtrace[$n - 3]['function'],
            'class' => $backtrace[$n - 3]['class'],
            'function' => $backtrace[$n - 4]['function'],
            'time' => date('Y-m-d H:i:s'),
            'error_heirarchy' => $heirarchy_tree
        );

        //Change the status variable, Avoid changing if success_message is to be set
        if ($error_group != 'success_message') {
            //Set the error variable
            self::$error_set = 1;
            self::$status = -2;
        }
        //Store the error data
        if ($error_group == '')
            self::$error['general'][] = $error_data;
        else
            self::$error[$error_group][] = $error_data;

        return;
    }

    /*
     *  Function to get the error
     * 
     *  @param      $error_group        Specific Name of the Group of which to fetch the Error
     *  @param      $append_group       Append the Error Group name at the start of the Group
     *                                  E.g. Error Group Name: Error
     *  @return     string   
     */

    public static function get_error($error_group = '', $append_error_group = FALSE) {
        /*
         * We've to decide if the user needs an extended view or a normal view
         *  Normal View
         *      The error Message
         * Extended View
         *      The error Message
         *      Line - 32, Method - Method Name, Class - Class Name 
         *      Function - Error Function Time - 10-9-2014 01:45:00
         * 
         */

        //Declare the varibles
        $parse_array = [];
        $return_variable = $error_message = '';
        $ret_array = [];

        //Get the Errors, check if only one Error Group is required or all
        if (!empty($error_group) AND isset(self::$error[$error_group])) {
            //Parse only the Error Group
            $parse_array[$error_group] = self::$error[$error_group];
        } else {
            //Parse the Whole Error Array
            $parse_array = self::$error;
        }

        //Generate the Errors, if set
        if (self::$error_set) {
            //Return the error message
            foreach ($parse_array as $k => $v) {
                foreach ($v AS $key => $val) {

                    if ($append_error_group OR $k == 'success_message')
                        $error_message .= $k . ': ';
                    $error_message .= $val['message'];
                    //Put the error in array
                    $ret_array[] = $error_message;
                    //Assign and nullify the $error_message
                    if ($return_variable != '')
                        $return_variable .= '<br />' . $error_message;
                    else
                        $return_variable .= $error_message;
                    $error_message = '';
                    //The user needs detailed view
                    if (self::DETAILED_ERROR) {
                        $return_variable .= '<br />Error Line - ' . $val['line_no'] . ', Calling Function - ' . $val['method'] . ', Calling Class - ' . $val['class'] . '<br />';
                        $return_variable .= 'Executing Function - ' . $val['function'] . ', Time - ' . $val['time'] . '<br />';
                        $return_variable .= 'Heirarchy - ' . $val['error_heirarchy'];
                    }
                }
            }
        } else {
            return [self::$status, ''];
        }

        return [self::$status, $return_variable, $ret_array];
    }

    /*
     *  Function to get the Success Message or Error
     *  @return     string   
     */

    public static function get_success_data() {
        /*
         * We've to decide if the user needs an extended view or a normal view
         *  Normal View
         *      The Success Message and the warnings
         * Extended View
         *      The Success Message
         *      But there are still warnings
         *          The warning message
         *              Line - 32, Method - Method Name, Class - Class Name 
         *              Function - Error Function Time - 10-9-2014 01:45:00
         *          The warning message
         *              Line - 32, Method - Method Name, Class - Class Name 
         *              Function - Error Function Time - 10-9-2014 01:45:00
         * 
         */

        //First of all check if success data is set, i.e. some array or object to Return;
        if (self::$success_data_set == 1) {
            //We've to return the data set
            return [self::$status, self::$success_data];
        }

        //If error is set
        if (self::$status)
            return [self::$status, self::$success_message];
        else
            return self::get_error();
    }

    /*
     *  The function to set the exclusive status of the errors
     * 
     *  @param     $status      status of the error message.
     */

    public static function set_exclusive_errorstatus($status) {
        //Verify the inputs
        if (!is_numeric($status)) {
            self::set_error('The status provided is not in the appropriate form.');
        }

        self::$status = $status;
        return;
    }

    /*
     *  The public function to be used by all the controllers while data returning
     * 
     *  @param      $append_error_group     Append the Name of the Error Group
     *  @param      $get_array              Get Errors in an Array
     *  @return     $status                 status of the error message.
     */

    public static function get_returndata($error_group = '', $append_error_group = FALSE, $get_array = FALSE) {
        //Check if error
        if (!self::check_error()) {
            //This is an error
            $errors = self::get_error($error_group, $append_error_group);
            if ($get_array)
                return [$errors[0], $errors[2]];
            else
                return [$errors[0], $errors[1]];
        }else {
            return self::get_success_data();
        }
    }

    /*
     *  The public function to check if error is present
     * 
     *  @param      $error_group            Error Group to check the Error for
     *                                      If not set, Error if any is checked
     *  @return     boolean TRUE/FALSE
     */

    public static function check_error($error_group = '') {
        //Check if to check error for a specific Group Only
        if ($error_group != '') {
            //Check if error exists for the mentioned Group, if key is set, it means error Exists
            if (isset(self::$error[$error_group]))
                return FALSE;
        }else {
            if (self::$error_set > 0) {
                //There is error
                return FALSE;
            }
        }

        //Means there is no error yet.
        return TRUE;
    }

}
