<?php

/**
 * BOUNCE HANDLER Class, Version 7.4
 *
 * Tries to extract information about why an email bounced.
 *
 * PHP version 5.3
 *
 * The BSD License
 * Copyright (c) 2006-forever, Chris Fortune http://cfortune.kics.bc.ca
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * - Neither the name of the BounceHandler nor the names of its contributors may
 *   be used to endorse or promote products derived from this
 *   software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Email
 * @package   BounceHandler
 * @author    Chris Fortune <cfortune@users.noreply.github.com>
 *            Original development. http://cfortune.kics.bc.ca
 * @author    Richard Bairwell <rbairwell@users.noreply.github.com>
 *            Code cleanups and restructuring. http://blog.rac.me.uk
 * @author    "Kanon" <unknown@invalid.tld>
 * @author    Jamie McClelland <jm@mayfirst.org>
 *            https://mayfirst.org/jamie-mcclelland
 * @author    Michael Cooper <unknown@invalid.tld>
 * @author    Thomas Seifert <mysnip@users.noreply.github.com>
 * @author    Tim Petrowsky <setzen@neuecouch.de>
 *            http://neuecouch.de
 * @author    Willy T. Koch <willytk@users.noreply.github.com>
 *            http://apeland.no
 * @author    ganeshaspeaks.com <unknown@invalid.tld>
 *            FBL development
 * @author    Richard Catto <unknown@invalid.tld>
 *            FBL development
 * @author    Scott Brynen <sbrynen@users.noreply.github.com>
 *            FBL development http://visioncritical.com
 *            https://github.com/visioncritical/PHP-Bounce-Handler
 * @copyright 2006-2014 Chris Fortune and others.
 * @license   http://opensource.org/licenses/BSD-2-Clause  BSD
 * @link      https://github.com/cfortune/PHP-Bounce-Handler/
 * @link      http://www.anti-spam-man.com/php_bouncehandler/v7.3/
 * @link      http://www.phpclasses.org/browse/file/11665.html
 */

/*
 * Class BounceHandler.
 *
 * Interprets email bounces.
 *
 * @category Email
 * @package  BounceHandler
 * @author   Multiple <cfortune@users.noreply.github.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause  BSD
 * @link     https://github.com/cfortune/PHP-Bounce-Handler/
 */

class BounceHandler
{

    /**
     * Extracted header information.
     *
     * @var array
     */
    public $head_hash = array();
    /**
     * Extracted ARF/FBL data.
     *
     * @var array
     */
    public $fbl_hash = array();
    /**
     * Not necessary(?)
     *
     * @var array
     */
    public $body_hash = array();
    /**
     * Set if this looks like a bounce.
     *
     * @var bool
     */
    public $looks_like_a_bounce = false;
    /**
     * Set if this looks like an ARF/FBL "abuse" request.
     *
     * @var bool
     */
    public $looks_like_an_FBL = false;
    /**
     * Set if this looks like an autoresponder.
     *
     * @var bool
     */
    public $looks_like_an_autoresponse = false;
    /**
     * Set if this looks like a Hotmail FBL request.
     *
     * @var bool
     */
    public $is_hotmail_fbl = false;
    /**
     * Regular expression to look for web beacons from emails.
     *
     * E.g. <img src="http://mysite.com/track.php?u=userId12345">
     *
     * @var string
     */
    public $web_beacon_preg_1 = "";
    /**
     * Regular expression to look for web beacons from emails.
     *
     * E.g. <img src="http://mysite.com/track.php?u=userId12345">
     *
     * @var string
     */
    public $web_beacon_preg_2 = "";
    /**
     * String to look for beacons in the email headers.
     *
     * E.g. X-my-custom-header: userId12345
     *
     * @var string
     */
    public $x_header_search_1 = "";

    /**
     * String to look for beacons in the email headers.
     *
     * E.g. X-my-custom-header: userId12345
     *
     * @var string
     */
    public $x_header_search_2 = "";

    /**
     * Type of email - either autoresponse, fbl, bounce or "false"
     *
     * @var string|bool
     */
    public $type = false;

    /**
     * Result of the $web_beacon_preg_1 regular expression.
     *
     * @var string
     */
    public $web_beacon_1 = "";

    /**
     * Result of the $web_beacon_preg_2 regular expression.
     *
     * @var string
     */
    public $web_beacon_2 = "";

    /**
     * FBL feedback type if any.
     *
     * @var string
     */
    public $feedback_type = "";

    /**
     * Result of the x_header_search_1 search.
     *
     * @var string
     */
    public $x_header_beacon_1 = "";

    /**
     * Result of the x_header_search_2 search.
     *
     * @var string
     */
    public $x_header_beacon_2 = "";

    /**
     * An accessor for $this->_bouncehandler->get_the_facts()[0]['action']
     *
     * Recommendation action for the email.
     *
     * Only useful for FBL's or if the output array only has one index
     *
     * @var string
     */
    public $action = "";

    /**
     * An accessor for $this->_bouncehandler->get_the_facts()[0]['status'].
     *
     * Status of the email.
     * Only useful for FBL's or if the output array only has one index.
     *
     * @var string
     */
    public $status = "";

    /**
     *  An accessor for $this->_bouncehandler->get_the_facts()[0]['subect'].
     *
     * Subject of the email.
     * Only useful for FBL's or if the output array only has one index.
     *
     * @var string
     */
    public $subject = "";

    /**
     * An accessor for $this->_bouncehandler->get_the_facts()[0]['recipient'].
     *
     * Recipient of the original email.
     * Only useful for FBL's or if the output array only has one index.
     *
     * @var string
     */
    public $recipient = "";
    /**
     * @var array
     */
    public $output = array();

    /**
     * Our cached list of bounce strings to look out for.
     *
     * @var array
     */
    private $_bouncelist = array();

    /**
     * Cached list of auto-respond subjects to look out for.
     *
     * @var array
     */
    private $_autorespondlist = array();

    /**
     * Cached list of bounce subjects to look out for.
     *
     * @var array
     */
    private $_bouncesubj = array();

    /**
     * @var array
     */
    private $_first_body_hash = array();

    /**
     * If this is an auto-response, how did we detect it?
     *
     * @var string
     */
    private $_autoresponse = '';


    /**
     * Constructor.
     *
     * If bouncelist, autorespondlist or bouncesubj is empty, then load them
     * in from BounceHandlerResponses.
     *
     * @param array $bouncelist      text in messages from which to figure out
     *                          kind of bounce this is.
     * @param array $autorespondlist triggers for autoresponders
     * @param array $bouncesubj      trigger subject lines for bounces
     */
    public function __construct(
        $bouncelist = array(),
        $autorespondlist = array(),
        $bouncesubj = array()
    ) {
        $this->_bouncelist = $bouncelist;
        $this->_autorespondlist = $autorespondlist;
        $this->_bouncesubj = $bouncesubj;
        if (empty($bouncelist) || empty($autorespondlist)
            || empty($bouncesubj)
        ) {
            if (empty($this->_bouncelist)) {
                $this->_bouncelist = BounceHandlerResponses::$bouncelist;
            }
            if (empty($this->_autorespondlist)) {
                $this->_autorespondlist = BounceHandlerResponses::$autorespondlist;
            }
            if (empty($bouncesubj)) {
                $this->_bouncesubj = BounceHandlerResponses::$bouncesubj;
            }
        }
        $this->init_bouncehandler();

    }

    /**
     * Most commonly used public method - quick and dirty email parsing.
     *
     * Usage: $multiArray = $this->get_the_facts($strEmail);
     *
     * @param string $eml Contents of the email.
     *
     * @return array
     */
    public function parse_email($eml)
    {
        return $this->get_the_facts($eml);
    }

    /**
     * Gets the facts about the email
     *
     * @param string $eml Contents of the email.
     *
     * @return array
     */
    public function get_the_facts($eml)
    {
        // fluff up the email
        $bounce = $this->init_bouncehandler($eml);
        if (strpos($bounce, "\r\n\r\n") !== false) {
            list($head, $body) = preg_split("/\r\n\r\n/", $bounce, 2);
        } else {
            list($head, $body) = array($bounce, '');
        }
        $this->head_hash = $this->parse_head($head);

        // parse the email into data structures
        $boundary = isset($this->head_hash['Content-type']['boundary'])
            ? $this->head_hash['Content-type']['boundary'] : '';
        $mime_sections = $this->parse_body_into_mime_sections($body, $boundary);
        $this->body_hash = preg_split("/\r\n/", $body);
        $this->_first_body_hash = isset($mime_sections['first_body_part'])
            ? $this->parse_head($mime_sections['first_body_part']) : array();

        $this->looks_like_a_bounce
            = $this->is_RFC1892_multipart_report() || $this->is_a_bounce();
        $this->looks_like_an_FBL = $this->is_an_ARF();
        $this->looks_like_an_autoresponse = $this->is_an_autoresponse();

        /* now we try all our weird text parsing methods (E-mail is weird!) */

        /**
         * Is it a feedback loop in abuse feedback reporting format (ARF)?
         *
         * @link http://en.wikipedia.org/wiki/Abuse_Reporting_Format
         *       #Abuse_Feedback_Reporting_Format_.28ARF.29
         */
        if ($this->looks_like_an_FBL) {
            $this->output[0]['action'] = 'failed';
            $this->output[0]['status'] = "5.7.1";
            $this->subject = trim(
                str_ireplace("Fw:", "", $this->head_hash['Subject'])
            );
            if ($this->is_hotmail_fbl === true) {
                // fill in the fbl_hash with sensible values
                $this->fbl_hash['Source-ip'] = '';
                $this->fbl_hash['Original-mail-from'] = '';
                $this->fbl_hash['Original-rcpt-to'] = '';
                $this->fbl_hash['Feedback-type'] = 'abuse';
                $this->fbl_hash['Content-disposition'] = 'inline';
                $this->fbl_hash['Content-type'] = 'message/feedback-report';
                $this->fbl_hash['User-agent'] = 'Hotmail FBL';
                if (isset($this->_first_body_hash['Date'])) {
                    $this->fbl_hash['Received-date']
                        = $this->_first_body_hash['Date'];
                }
                if (isset($this->head_hash['Subject'])
                    && preg_match(
                        '/complaint about message from ([0-9.]+)/',
                        $this->head_hash['Subject'], $matches
                    )
                ) {
                    $this->fbl_hash['Source-ip'] = $matches[1];
                }
                if (!empty($this->recipient)) {
                    $this->fbl_hash['Original-rcpt-to'] = $this->recipient;
                }
                if (isset($this->_first_body_hash['X-sid-pra'])) {
                    $this->fbl_hash['Original-mail-from']
                        = $this->_first_body_hash['X-sid-pra'];
                }
            } else {
                $this->fbl_hash = $this->standard_parser(
                    $mime_sections['machine_parsable_body_part']
                );
                $returnedhash = $this->standard_parser(
                    $mime_sections['returned_message_body_part']
                );
                if (!empty($returnedhash['Return-path'])) {
                    $this->fbl_hash['Original-mail-from']
                        = $returnedhash['Return-path'];
                } elseif (empty($this->fbl_hash['Original-mail-from'])
                    && !empty($returnedhash['From'])
                ) {
                    $this->fbl_hash['Original-mail-from']
                        = $returnedhash['From'];
                }
                if (empty($this->fbl_hash['Original-rcpt-to'])
                    && !empty($this->fbl_hash['Removal-recipient'])
                ) {
                    $this->fbl_hash['Original-rcpt-to']
                        = $this->fbl_hash['Removal-recipient'];
                } elseif (isset($returnedhash['To'])) {
                    $this->fbl_hash['Original-rcpt-to'] = $returnedhash['To'];
                } else {
                    $this->fbl_hash['Original-rcpt-to'] = '';
                }
                if (!isset($this->fbl_hash['Source-ip'])) {
                    if (!empty($returnedhash['X-originating-ip'])) {
                        $this->fbl_hash['Source-ip']
                            = $this->_strip_angle_brackets(
                                $returnedhash['X-originating-ip']
                            );
                    } else {
                        $this->fbl_hash['Source-ip'] = '';
                    }
                }
            }
            // warning, some servers will remove the name of the original
            // intended recipient from the FBL report,
            // replacing it with redacted@rcpt-hostname.com, making it utterly
            // useless, of course (unless you used a web-beacon).
            // here we try our best to give you the actual intended recipient,
            // if possible.
            if (preg_match(
                '/Undisclosed|redacted/i',
                $this->fbl_hash['Original-rcpt-to']
            )
                && isset($this->fbl_hash['Removal-recipient'])
            ) {
                $this->fbl_hash['Original-rcpt-to']
                    = @$this->fbl_hash['Removal-recipient'];
            }
            if (empty($this->fbl_hash['Received-date'])
                && !empty($this->fbl_hash[@'Arrival-date'])
            ) {
                $this->fbl_hash['Received-date']
                    = @$this->fbl_hash['Arrival-date'];
            }
            $this->fbl_hash['Original-mail-from'] = $this->_strip_angle_brackets(
                @$this->fbl_hash['Original-mail-from']
            );
            $this->fbl_hash['Original-rcpt-to'] = $this->_strip_angle_brackets(
                @$this->fbl_hash['Original-rcpt-to']
            );
            $this->output[0]['recipient'] = $this->fbl_hash['Original-rcpt-to'];
        } elseif ($this->looks_like_an_autoresponse) {
            // is this an autoresponse ?
            $this->output[0]['action'] = 'autoresponse';
            $this->output[0]['autoresponse'] = $this->_autoresponse;
            $this->output[0]['status'] = '2.0';
            // grab the first recipient and break
            $this->output[0]['recipient']
                = isset($this->head_hash['Return-path'])
                ? $this->_strip_angle_brackets($this->head_hash['Return-path'])
                : '';
            if (empty($this->output[0]['recipient'])) {
                $this->output[0]['recipient']
                    = isset($this->head_hash['From'])
                    ? $this->_strip_angle_brackets($this->head_hash['From'])
                    : '';
            }
            if (empty($this->output[0]['recipient'])) {
                $arrFailed = $this->find_email_addresses($body);
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    break;
                }
            }
        } else if ($this->is_RFC1892_multipart_report() === true) {

            $rpt_hash = $this->parse_machine_parsable_body_part(
                $mime_sections['machine_parsable_body_part']
            );
            if (isset($rpt_hash['per_recipient'])) {
                for ($i = 0; $i < count($rpt_hash['per_recipient']); $i++) {
                    $this->output[$i]['recipient'] = $this->find_recipient(
                        $rpt_hash['per_recipient'][$i]
                    );
                    $mycode = @$this->format_status_code(
                        $rpt_hash['per_recipient'][$i]['Status']
                    );
                    $this->output[$i]['status'] = $mycode['code'];

                    $this->output[$i]['action']
                        = $this->get_action_from_status_code($mycode['code']);
                }
            } else {
                $arrFailed = $this->find_email_addresses(
                    $mime_sections['first_body_part']
                );
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    $this->output[$j]['status']
                        = $this->get_status_code_from_text(
                            $this->output[$j]['recipient'], 0
                        );
                    $this->output[$j]['action']
                        = $this->get_action_from_status_code(
                            $this->output[$j]['status']
                        );
                }
            }
        } else if (isset($this->head_hash['X-failed-recipients'])) {
            //  Busted Exim MTA
            //  Up to 50 email addresses can be listed on each header.
            //  There can be multiple X-Failed-Recipients: headers. - (not supported)
            $arrFailed = explode(',', $this->head_hash['X-failed-recipients']);
            for ($j = 0; $j < count($arrFailed); $j++) {
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text(
                    $this->output[$j]['recipient'], 0
                );
                $this->output[$j]['action']
                    = $this->get_action_from_status_code(
                        $this->output[$j]['status']
                    );
                $this->looks_like_a_bounce = true;
            }
        } else {
            if (!empty($boundary)) {
                // oh god it could be anything, but at least it has mime parts,
                // so let's try anyway
                $arrFailed = $this->find_email_addresses(
                    $mime_sections['first_body_part']
                );
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    $this->output[$j]['status']
                        = $this->get_status_code_from_text(
                            $this->output[$j]['recipient'], 0
                        );
                    $this->output[$j]['action']
                        = $this->get_action_from_status_code(
                            $this->output[$j]['status']
                        );
                }
            } else {
                // last ditch attempt
                // could possibly produce erroneous output, or be very resource
                // consuming, so be careful.  You should comment out this
                // section if you are very concerned
                // about 100% accuracy or if you want very fast performance.
                // Leave it turned on if you know that all messages to be
                // analyzed are bounces.
                $arrFailed = $this->find_email_addresses($body);
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    $this->output[$j]['status']
                        = $this->get_status_code_from_text(
                            $this->output[$j]['recipient'], 0
                        );
                    $this->output[$j]['action']
                        = $this->get_action_from_status_code(
                            $this->output[$j]['status']
                        );
                }
            }
        }
        // else if()..... add a parser for your busted-ass MTA here

        // remove empty array indices
        $tmp = array();
        foreach ($this->output as $arr) {
            if (empty($arr['recipient']) && empty($arr['status'])
                && empty($arr['action'])
            ) {
                continue;
            }
            $tmp[] = $arr;
        }
        $this->output = $tmp;
        // accessors
        /*if it is an FBL, you could use the class variables to access the
        data (Unlike Multipart-reports, FBL's report only one bounce)
        */
        $this->type = $this->find_type();
        $this->action = isset($this->output[0]['action'])
            ? $this->output[0]['action'] : '';
        $this->status = isset($this->output[0]['status'])
            ? $this->output[0]['status'] : '';
        $this->subject = ($this->subject) ? $this->subject
            : $this->head_hash['Subject'];
        $this->recipient = isset($this->output[0]['recipient'])
            ? $this->output[0]['recipient'] : '';
        $this->feedback_type = (isset($this->fbl_hash['Feedback-type']))
            ? $this->fbl_hash['Feedback-type'] : "";

        // sniff out any web beacons
        if ($this->web_beacon_preg_1) {
            $this->web_beacon_1 = $this->find_web_beacon(
                $body, $this->web_beacon_preg_1
            );
        }
        if ($this->web_beacon_preg_2) {
            $this->web_beacon_2 = $this->find_web_beacon(
                $body, $this->web_beacon_preg_2
            );
        }
        if ($this->x_header_search_1) {
            $this->x_header_beacon_1 = $this->find_x_header(
                $this->x_header_search_1
            );
        }
        if ($this->x_header_search_2) {
            $this->x_header_beacon_2 = $this->find_x_header(
                $this->x_header_search_2
            );
        }

        return $this->output;
    }


    /**
     * Setup/reset thge bounce handler.
     *
     * @param string $blob   Inbound email
     * @param string $format Not currently used
     *
     * @return string Contents of email
     */
    function init_bouncehandler($blob = '', $format = 'string')
    {
        $this->head_hash = array();
        $this->fbl_hash = array();
        $this->body_hash = array();
        $this->looks_like_a_bounce = false;
        $this->looks_like_an_FBL = false;
        $this->is_hotmail_fbl = false;
        $this->type = false;
        $this->feedback_type = "";
        $this->action = "";
        $this->status = "";
        $this->subject = "";
        $this->recipient = "";
        $this->output = array();
        $this->output[0]['action'] = '';
        $this->output[0]['status'] = '';
        $this->output[0]['recipient'] = '';
        $strEmail = '';
        if ('' !== $blob) {
            // TODO: accept several formats (XML, string, array)
            // currently accepts only string
            //if($format=='xml_array'){
            //    $strEmail = "";
            //    $out = "";
            //    for($i=0; $i<$blob; $i++){
            //        $out = preg_replace("/<HEADER>/i", "", $blob[$i]);
            //        $out = preg_replace("/</HEADER>/i", "", $out);
            //        $out = preg_replace("/<MESSAGE>/i", "", $out);
            //        $out = preg_replace("/</MESSAGE>/i", "", $out);
            //        $out = rtrim($out) . "\r\n";
            //        $strEmail .= $out;
            //    }
            //}
            //else if($format=='string'){

            $strEmail = str_replace("\r\n", "\n", $blob);    // line returns 1
            $strEmail = str_replace("\n", "\r\n", $strEmail);// line returns 2
            // $strEmail = str_replace("=\r\n", "", $strEmail);
            // remove MIME line breaks (would never exist as #1 above would have
            // have dealt with)
            // $strEmail = str_replace("=3D", "=", $strEmail);
            // equals sign - dealt with in the MIME decode section now
            // $strEmail = str_replace("=09", "  ", $strEmail); // tabs

            //}
            //else if($format=='array'){
            //    $strEmail = "";
            //    for($i=0; $i<$blob; $i++){
            //        $strEmail .= rtrim($blob[$i]) . "\r\n";
            //    }
            //}
        }
        return $strEmail;
    }


    /**
     * Try to extract useful info from the headers bounces produced by
     * busted MTAs.
     *
     * @param string|array $headers Headers of the email
     *
     * @return array
     */
    function parse_head($headers)
    {
        if (!is_array($headers)) {
            $headers = explode("\r\n", $headers);
        }
        $hash = $this->standard_parser($headers);
        if (isset($hash['Content-type'])) {
            //preg_match('/Multipart\/Report/i', $hash['Content-type'])){
            $multipart_report = explode(';', $hash['Content-type']);
            $hash['Content-type'] = '';
            $hash['Content-type']['type'] = strtolower($multipart_report[0]);
            foreach ($multipart_report as $mr) {
                if (preg_match('/([^=.]*?)=(.*)/i', $mr, $matches)) {
                    // didn't work when the content-type boundary ID contained
                    // an equal sign,
                    // that exists in bounces from many Exchange servers
                    //if(preg_match('/([a-z]*)=(.*)?/i', $mr, $matches)){
                    $hash['Content-type'][strtolower(trim($matches[1]))]
                        = str_replace('"', '', $matches[2]);
                }
            }
        }

        return $hash;
    }

    /**
     * Try and understand information from the headers of the email.
     *
     * @param string|array $content Header of the email
     *
     * @return array
     */
    function standard_parser($content)
    {
        // associative array orstr
        // receives email head as array of lines
        // simple parse (Entity: value\n)
        $hash = array('Received' => '');
        if (!is_array($content)) {
            $content = explode("\r\n", $content);
        }
        foreach ($content as $line) {
            if (preg_match('/^([^\s.]*):\s*(.*)\s*/', $line, $array)) {
                $entity = ucfirst(strtolower($array[1]));
                if (isset($array[2]) && strpos($array[2], '=?') !== false) {
                    // decode MIME Header encoding (subject lines etc)
                    $array[2] = iconv_mime_decode(
                        $array[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8"
                    );
                }
                if (empty($hash[$entity])) {
                    $hash[$entity] = trim($array[2]);
                } else if ($hash['Received']) {
                    // grab extra Received headers :(
                    // pile it on with pipe delimiters,
                    // oh well, SMTP is broken in this way
                    if ($entity and $array[2] and $array[2] != $hash[$entity]) {
                        $hash[$entity] .= "|" . trim($array[2]);
                    }
                }
            } elseif (isset($line) && isset($entity)
                && preg_match(
                    '/^\s+(.+)\s*/', $line, $array
                )
                && $entity
            ) {
                $line = trim($line);
                if (true === isset($array[1])
                    && strpos($array[1], '=?') !== false
                ) {
                    $line = iconv_mime_decode(
                        $array[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8"
                    );
                }
                $hash[$entity] .= ' ' . $line;
            }
        }
        // special formatting
        $hash['Received'] = @explode('|', $hash['Received']);
        $hash['Subject'] = isset($hash['Subject']) ? $hash['Subject'] : '';
        return $hash;
    }

    /**
     * Split an email into multiple mime sections
     *
     * @param string|array $body     Body of the email
     * @param string       $boundary The boundary MIME separator.
     *
     * @return array
     */
    function parse_body_into_mime_sections($body, $boundary)
    {

        if (!$boundary) {
            return array();
        }
        if (is_array($body)) {
            $body = implode("\r\n", $body);
        }
        $body = explode(rtrim($boundary, '='), $body);

        $mime_sections['first_body_part'] = isset($body[1])
            ? $this->contenttype_decode($body[1]) : ''; // proper MIME decode
        $mime_sections['machine_parsable_body_part'] = isset($body[2])
            ? $this->contenttype_decode($body[2]) : '';
        $mime_sections['returned_message_body_part'] = isset($body[3])
            ? $this->contenttype_decode($body[3]) : '';
        return $mime_sections;
    }

    /**
     * Decode a content transfer-encoded part of the email.
     *
     * @param string $mimepart MIME encoded email body
     *
     * @return string
     */
    function contenttype_decode($mimepart)
    {
        $encoding = '7bit';
        $decoded = '';
        foreach (explode("\r\n", $mimepart) as $line) {
            if (preg_match(
                "/^Content-Transfer-Encoding:\s*(\S+)/", $line, $match
            )) {
                $encoding = $match[1];
                $decoded .= $line . "\r\n";
            } else {
                switch ($encoding) {
                case 'quoted-printable':
                    if (substr($line, -1) == '=') {
                        $line = substr($line, 0, -1);
                    } else {
                        $line .= "\r\n";
                    }
                    $decoded .= preg_replace_callback(
                        "/=([0-9A-F][0-9A-F])/", function ($matches) {
                            return chr(hexdec($matches[0]));
                        }, $line
                    );
                    break;
                case 'base64':
                    $decoded .= base64_decode($line);
                    break;
                default:                                  // 7bit, 8bit, binary
                    $decoded .= $line . "\r\n";
                }
            }
        }

        return $decoded;
    }

    /**
     * Sees if this is an "obvious bounce".
     *
     * @return bool
     */
    function is_a_bounce()
    {
        if (true === isset($this->head_hash['From'])
            && preg_match(
                '/^(postmaster|mailer-daemon)\@?/i', $this->head_hash['From']
            )
        ) {
            return true;
        }
        foreach ($this->_bouncesubj as $s) {
            if (preg_match("/^$s/i", $this->head_hash['Subject'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sees if this is an obvious "Abuse reporting format" email.
     *
     * @return bool
     */
    function is_an_ARF()
    {
        if (isset($this->head_hash['Content-type']['report-type'])
            && preg_match(
                '/feedback-report/',
                $this->head_hash['Content-type']['report-type']
            )
        ) {
            return true;
        }
        if (isset($this->head_hash['X-loop'])
            && preg_match(
                '/scomp/', $this->head_hash['X-loop']
            )
        ) {
            return true;
        }
        if (isset($this->head_hash['X-hmxmroriginalrecipient'])) {
            $this->is_hotmail_fbl = true;
            $this->recipient = $this->head_hash['X-hmxmroriginalrecipient'];

            return true;
        }
        if (isset($this->_first_body_hash['X-hmxmroriginalrecipient'])) {
            $this->is_hotmail_fbl = true;
            $this->recipient
                = $this->_first_body_hash['X-hmxmroriginalrecipient'];

            return true;
        }

        return false;
    }

    /**
     * Sees if this is an obvious autoresponder email.
     *
     * @return bool
     */
    function is_an_autoresponse()
    {
        if (true === isset($this->head_hash['Auto-submitted'])) {
            if (preg_match(
                '/auto-notified|vacation|away/i',
                $this->head_hash['Auto-submitted']
            )) {
                $this->_autoresponse
                    = 'Auto-submitted: ' . $this->head_hash['Auto-submitted'];
                return true;
            }
        }
        if (true === isset($this->head_hash['X-autorespond'])) {
            if (preg_match(
                '/auto-notified|vacation|away/i',
                $this->head_hash['X-autorespond']
            )) {
                $this->_autoresponse
                    = 'X-autorespond: ' . $this->head_hash['X-autorespond'];
                return true;
            }
        }
        if (true === isset($this->head_hash['Precedence'])
            && preg_match(
                '/^auto-reply/i', $this->head_hash['Precedence']
            )
        ) {
            $this->_autoresponse
                = 'Precedence: ' . $this->head_hash['Precedence'];
            return true;
        }
        if (true === isset($this->head_hash['X-Precedence'])
            && preg_match(
                '/^auto-reply/i', $this->head_hash['X-Precedence']
            )
        ) {
            $this->_autoresponse
                = 'X-Precedence: ' . $this->head_hash['X-Precedence'];
            return true;
        }
        if (true == isset($this->head_hash['Subject'])) {
            foreach ($this->_autorespondlist as $a) {
                $result = preg_match("/$a/i", $this->head_hash['Subject']);
                if (false === $result) {
                    die('Bad autoresponse regular expression ('
                        . preg_last_error() . ') while processing:' . $a);
                }
                if (1 === $result) {
                    $this->_autoresponse = $this->head_hash['Subject'];
                    return true;
                }
            }
        }


        return false;
    }

    /**
     * Strip angled brackets.
     *
     * @param string $recipient Removes angled brackets from an email address.
     *
     * @return string
     */
    private function _strip_angle_brackets($recipient)
    {
        if (preg_match('/[<[](.*)[>\]]/', $recipient, $matches)) {
            return trim($matches[1]);
        } else {
            return trim($recipient);
        }
    }

    /**
     * Finds email addresses in a body.
     *
     * @param string $first_body_part Body of the email
     *
     * @return array
     *
     * @TODO Appears that it should return multiple email addresses.
     */
    function find_email_addresses($first_body_part)
    {
        /**
         * Regular expression for searching for email addresses
         *
         * @link https://bitbucket.org/bairwell/emailcheck/src/
         *       81c6a1a25d28a8abda1673ae1fbec3ba55b72bce/emailcheck.php
         *      Doesn't currently do any "likely domain valid" or similar checks
         */
        $regExp
            = '/(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^'.
            '_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-'.
            '\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z'.
            '0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25['.
            '0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|'.
            '[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-'.
            '\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/iS';

        $matched = preg_match($regExp, $first_body_part, $matches);
        if (1 === $matched) {
            return array($matches[0]);
        } else {
            return array();
        }
    }

    /**
     * Is this an RFC 1892 multiple return email?
     *
     * @param array $head_hash Associative array of headers. If not set, will
     *                         use $this->head_hash
     *
     * @return bool
     */
    public function is_RFC1892_multipart_report($head_hash = array())
    {
        if (empty($head_hash)) {
            $head_hash = $this->head_hash;
        }
        if (isset($head_hash['Content-type']['type'])
            && isset($head_hash['Content-type']['report-type'])
            && isset($head_hash['Content-type']['boundary'])
            && 'multipart/report' === $head_hash['Content-type']['type']
            && 'delivery-status' === $head_hash['Content-type']['report-type']
            && '' !== $head_hash['Content-type']['boundary']
        ) {
            return true;
        }
        return false;
    }

    /**
     * Parse delivery service notification sections.
     *
     * @param string $str Email
     *
     * @return array
     */
    function parse_machine_parsable_body_part($str)
    {
        //Per-Message DSN fields
        $hash = $this->parse_dsn_fields($str);
        $hash['mime_header'] = $this->standard_parser($hash['mime_header']);
        $hash['per_message'] = isset($hash['per_message'])
            ? $this->standard_parser($hash['per_message']) : array();
        if (isset($hash['per_message']['X-postfix-sender'])) {
            $arr = explode(';', $hash['per_message']['X-postfix-sender']);
            $hash['per_message']['X-postfix-sender'] = '';
            $hash['per_message']['X-postfix-sender']['type'] = @trim($arr[0]);
            $hash['per_message']['X-postfix-sender']['addr'] = @trim($arr[1]);
        }
        if (isset($hash['per_message']['Reporting-mta'])) {
            $arr = explode(';', $hash['per_message']['Reporting-mta']);
            $hash['per_message']['Reporting-mta'] = '';
            $hash['per_message']['Reporting-mta']['type'] = @trim($arr[0]);
            $hash['per_message']['Reporting-mta']['addr'] = @trim($arr[1]);
        }
        //Per-Recipient DSN fields
        if (isset($hash['per_recipient'])) {
            for ($i = 0; $i < count($hash['per_recipient']); $i++) {
                $temp = $this->standard_parser(
                    explode("\r\n", $hash['per_recipient'][$i])
                );
                $arr = isset($temp['Final-recipient']) ? explode(
                    ';', $temp['Final-recipient']
                ) : array();
                $temp['Final-recipient'] = $this->format_final_recipient_array(
                    $arr
                );
                //$temp['Final-recipient']['type'] = trim($arr[0]);
                //$temp['Final-recipient']['addr'] = trim($arr[1]);
                $temp['Original-recipient'] = array();
                $temp['Original-recipient']['type'] = isset($arr[0]) ? trim(
                    $arr[0]
                ) : '';
                $temp['Original-recipient']['addr'] = isset($arr[1]) ? trim(
                    $arr[1]
                ) : '';
                $arr = isset($temp['Diagnostic-code']) ? explode(
                    ';', $temp['Diagnostic-code']
                ) : array();
                $temp['Diagnostic-code'] = array();
                $temp['Diagnostic-code']['type'] = isset($arr[0]) ? trim(
                    $arr[0]
                ) : '';
                $temp['Diagnostic-code']['text'] = isset($arr[1]) ? trim(
                    $arr[1]
                ) : '';
                // now this is weird: plenty of times you see the status code
                // is a permanent failure,
                // but the diagnostic code is a temporary failure.  So we will
                // assert the most general
                // temporary failure in this case.
                $ddc = $this->decode_diagnostic_code(
                    $temp['Diagnostic-code']['text']
                );
                $judgement = $this->get_action_from_status_code($ddc);
                if ($judgement == 'transient') {
                    if (stristr($temp['Action'], 'failed') !== false) {
                        $temp['Action'] = 'transient';
                        $temp['Status'] = '4.3.0';
                    }
                }
                $hash['per_recipient'][$i] = '';
                $hash['per_recipient'][$i] = $temp;
            }
        }

        return $hash;
    }

    /**
     * Parse delivery service notification fields.
     *
     * @param array|string $dsn_fields List of fields.
     *
     * @return array
     */
    function parse_dsn_fields($dsn_fields)
    {
        if (!is_array($dsn_fields)) {
            $dsn_fields = explode("\r\n\r\n", $dsn_fields);
        }
        $hash = array();
        $j = 0;
        reset($dsn_fields);
        for ($i = 0; $i < count($dsn_fields); $i++) {
            $dsn_fields[$i] = trim($dsn_fields[$i]);
            if ($i == 0) {
                $hash['mime_header'] = $dsn_fields[0];
            } elseif ($i == 1
                && !preg_match(
                    '/(Final|Original)-Recipient/', $dsn_fields[1]
                )
            ) {
                // some mta's don't output the per_message part, which means
                // the second element in the array should really be
                // per_recipient - test with Final-Recipient - which should
                // always indicate that the part is a per_recipient part
                $hash['per_message'] = $dsn_fields[1];
            } else {
                if ($dsn_fields[$i] == '--') {
                    continue;
                }
                $hash['per_recipient'][$j] = $dsn_fields[$i];
                $j++;
            }
        }

        return $hash;
    }


    /**
     * Take a line like "4.2.12 This is an error" and return  "4.2.12" and
     * "This is an error".
     *
     * @param array $arr Input string.
     *
     * @return array
     */
    private function format_final_recipient_array($arr)
    {
        $output = array(
            'addr' => '',
            'type' => ''
        );
        if (isset($arr[1])) {
            if (strpos($arr[0], '@') !== false) {
                $output['addr'] = $this->_strip_angle_brackets($arr[0]);
                $output['type'] = (!empty($arr[1])) ? trim($arr[1]) : 'unknown';
            } else {
                $output['type'] = trim($arr[0]);
                $output['addr'] = $this->_strip_angle_brackets($arr[1]);
            }
        } elseif (isset($arr[0])) {
            if (strpos($arr[0], '@') !== false) {
                $output['addr'] = $this->_strip_angle_brackets($arr[0]);
                $output['type'] = 'unknown';
            }
        }

        return $output;
    }

    /**
     * Decode the diagnostic code into just the code number.
     *
     * @param string $dcode The diagnostic code
     *
     * @return string
     */
    function decode_diagnostic_code($dcode)
    {
        if (preg_match("/(\d\.\d\.\d)\s/", $dcode, $array)) {
            return $array[1];
        } else if (preg_match("/(\d\d\d)\s/", $dcode, $array)) {
            return $array[1];
        }

        return '';
    }

    /**
     * Get a brief status/recommend action from the status code.
     *
     * @param string $code The status code string supplied.
     *
     * @return string Either "success", "transient", "failed" or "" (unknown).
     */
    function get_action_from_status_code($code)
    {
        if ($code == '') {
            return '';
        }
        $ret = $this->format_status_code($code);
        /**
         * We weren't able to read the code
         */
        if ($ret['code'] === '') {
            return '';
        }
        /**
         * Work out the rough status from the first digit of the code
         */
        switch (substr($ret['code'], 0, 1)) {
        case(2):
            return 'success';
            break;
        case(4):
            return 'transient';
            break;
        case(5):
            return 'failed';
            break;
        default:
            return '';
            break;
        }
    }

    /**
     * Extract the code and text from a status code string.
     *
     * @param string $code   A status code string in the format 12.34.56 Reason or 123456 reason
     *                     Reason or 123456 reason
     * @param bool   $strict Only accept triplets (12.34.56) and not that
     *                     "breaks RFC" 12.34 format
     *
     * @return array Associative array containing code (two or three decimal
     * separated numbers) and text
     */
    function format_status_code($code, $strict = false)
    {
        $ret = array('code' => '', 'text' => '');
        $matches = array();
        if (preg_match(
            '/([245]\.[01234567]\.\d{1,2})\s*(.*)/', $code, $matches
        )) {
            $ret['code'] = $matches[1];
            $ret['text'] = $matches[2];
        } else if (preg_match(
            '/([245])([01234567])(\d{1,2})\s*(.*)/', $code, $matches
        )) {
            $ret['code'] = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
            $ret['text'] = $matches[4];
        } else if (false === $strict
            && preg_match(
                '/([245]\.[01234567])\s*(.*)/', $code, $matches
            )
        ) {
            /**
             * Handle major.minor code style (which is against RFCs - should
             * always be major.minor.sub)
             */
            $ret['code'] = $matches[1] . '.0';
            $ret['text'] = $matches[2];
        } else if (false === $strict
            && preg_match(
                '/([245])([01234567])\s*(.*)/', $code, $matches
            )
        ) {
            /**
             * Handle major.minor code style (which is against RFCs - should
             * always be major.minor.sub)
             */
            $ret['code'] = $matches[1] . '.' . $matches[2] . '.0';
            $ret['text'] = $matches[3];
        }
        return $ret;
    }

    /**
     * Find the recipient from either the original-recipient or
     * final-recipieint settings.
     *
     * @param array $per_rcpt Headers
     *
     * @return string Email address
     */
    function find_recipient($per_rcpt)
    {
        $recipient = '';
        if ($per_rcpt['Original-recipient']['addr'] !== '') {
            $recipient = $per_rcpt['Original-recipient']['addr'];
        } else if ($per_rcpt['Final-recipient']['addr'] !== '') {
            $recipient = $per_rcpt['Final-recipient']['addr'];
        }
        $recipient = $this->_strip_angle_brackets($recipient);

        return $recipient;
    }

    /**
     * @param $recipient
     * @param $index
     *
     * @return string
     */
    function get_status_code_from_text($recipient, $index)
    {
        for ($i = $index; $i < count($this->body_hash); $i++) {
            $line = trim($this->body_hash[$i]);

            //skip Message-ID lines
            if (stripos($line, 'Message-ID') !== false) {
                continue;
            }

            /* recurse into the email if you find the recipient **/
            if (stristr($line, $recipient) !== false) {
                // the status code MIGHT be in the next few lines after the
                // recipient line,
                // depending on the message from the foreign host...
                $status_code = $this->get_status_code_from_text(
                    $recipient, $i + 1
                );
                if ($status_code) {
                    return $status_code;
                }

            }

            /******** exit conditions ********/
            // if it's the end of the human readable part in this stupid bounce
            if (stristr($line, '------ This is a copy of the message') !== false
            ) {
                break;
            }
            //if we see an email address other than our current recipient's,
            if (count($this->find_email_addresses($line)) >= 1
                && stristr($line, $recipient) === false
                && strstr($line, 'FROM:<') === false
            ) {
                // Kanon added this line because Hotmail puts the e-mail
                //address too soon and there actually is error message stuff
                //after it.
                break;
            }

            //******** pattern matching ********/
            foreach ($this->_bouncelist as $bouncetext => $bouncecode) {
                if (preg_match("/$bouncetext/i", $line, $matches)) {
                    return (isset($matches[1])) ? $matches[1] : $bouncecode;
                }
            }

            // Search for a rfc3463 style return code
            if (preg_match(
                '/\W([245]\.[01234567]\.[0-9]{1,2})\W/', $line, $matches
            )) {
                return $matches[1];
                // ??? this seems somewhat redundant
                // $mycode = str_replace('.', '', $matches[1]);
                // $mycode = $this->format_status_code($mycode);
                // return implode('.', $mycode['code']);  #x.y.z format
            }

            // search for RFC2821 return code
            // thanks to mark.tolman@gmail.com
            // Maybe at some point it should have it's own place within the
            // main parsing scheme (at line 88)
            if (preg_match('/\]?: ([45][01257][012345]) /', $line, $matches)
                || preg_match(
                    '/^([45][01257][012345]) (?:.*?)(?:denied|inactive|'.
                    'deactivated|rejected|disabled|unknown|no such|'.
                    'not (?:our|activated|a valid))+/i',
                    $line, $matches
                )
            ) {
                $mycode = $matches[1];
                // map RFC2821 -> RFC3463 codes
                if ($mycode == '550' || $mycode == '551' || $mycode == '553'
                    || $mycode == '554'
                ) {
                    // perm error
                    return '5.1.1';
                } elseif ($mycode == '452' || $mycode == '552') {
                    // mailbox full
                    return '4.2.2';
                } elseif ($mycode == '450' || $mycode == '421') {
                    // temp unavailable
                    return '4.3.2';
                }
                // ???$mycode = $this->format_status_code($mycode);
                // ???return implode('.', $mycode['code']);
            }

        }

        return '5.5.0';  // other or unknown status
    }


    /**
     * Returns the type of email - either autoresponse, fbl, bounce or "false".
     *
     * @return bool|string
     */
    function find_type()
    {
        if ($this->looks_like_an_autoresponse) {
            return "autoresponse";
        } elseif ($this->looks_like_an_FBL) {
            return "fbl";
        } elseif ($this->looks_like_a_bounce) {
            return "bounce";
        } else {
            return false;
        }
    }

    // look for common auto-responders

    /**
     * Search for a web beacon in the email body.
     *
     * @param string $body Email body.
     * @param string $preg Regular expression to look for.
     *
     * @return string
     */
    public function find_web_beacon($body, $preg)
    {
        if (!isset($preg) || !$preg) {
            return '';
        }
        if (preg_match($preg, $body, $matches)) {
            return $matches[1];
        }

        return '';
    }


    /**
     * use a PECL regular expression to find the web beacon
     *
     * @param string $xheader Header string to look for.
     *
     * @return string
     */
    public function find_x_header($xheader)
    {
        $xheader = ucfirst(strtolower($xheader));
        // check the header
        if (isset($this->head_hash[$xheader])) {
            return $this->head_hash[$xheader];
        }
        // check the body too
        $tmp_body_hash = $this->standard_parser($this->body_hash);
        if (isset($tmp_body_hash[$xheader])) {
            return $tmp_body_hash[$xheader];
        }

        return '';
    }

    /**
     * @param $mime_sections
     *
     * @return array
     */
    function get_head_from_returned_message_body_part($mime_sections)
    {
        $temp = explode(
            "\r\n\r\n", $mime_sections['returned_message_body_part']
        );
        $head = $this->standard_parser($temp[1]);
        $head['From'] = $this->extract_address($head['From']);
        $head['To'] = $this->extract_address($head['To']);

        return $head;
    }

    /**
     * @param $str
     *
     * @return mixed
     */
    function extract_address($str)
    {
        $from = '';
        $from_stuff = preg_split('/[ \"\'\<\>:\(\)\[\]]/', $str);
        foreach ($from_stuff as $things) {
            if (strpos($things, '@') !== false) {
                $from = $things;
            }
        }

        return $from;
    }

    /**
     * Format a status code into a HTML marked up reason.
     *
     * @param string $code                   A status code line.
     * @param array  $status_code_classes    Rough description of status code.
     * @param array  $status_code_subclasses Details of each specific subcode.
     *
     * @return string HTML marked up reason
     */
    function fetch_status_messages(
        $code, $status_code_classes = array(), $status_code_subclasses = array()
    ) {
        $array = $this->fetch_status_message_as_array(
            $code, $status_code_classes, $status_code_subclasses
        );
        $str = '<p><b>' . $array['title'] . '</b> - ' . $array['description']
            . ' <b>' . $array['sub_title'] . '</b> - '
            . $array['sub_description'];
        return $str;
    }

    /**
     * Get human readable details of an SMTP status code.
     *
     * Loads in bounce_statuscodes if $status_code_classes or
     * $status_code_subclasses is empty.
     *
     * @param string $code                   A status code line or number.
     * @param array  $status_code_classes    Rough description of the code.
     * @param array  $status_code_subclasses Details of each specific subcode.
     *
     * @return array Human readable details of the code.
     */
    public function fetch_status_message_as_array(
        $code,
        $status_code_classes = array(),
        $status_code_subclasses = array()
    ) {
        $code_classes = $status_code_classes;
        $sub_classes = $status_code_subclasses;
        /**
         * Load from the provided BounceHandlerStatusCodes if not set
         */
        if (empty($code_classes) || empty($sub_classes)) {
            if (empty($code_classes)) {
                $code_classes = BounceHandlerStatusCodes::getClasses();
            }
            if (empty($sub_classes)) {
                $sub_classes = BounceHandlerStatusCodes::getSubClasses();
            }
        }
        $return = array(
            'input_code' => $code,
            'formatted_code_code' => '',
            'formatted_code_text=' > '',
            'major_code' => '',
            'sub_code' => '',
            'title' => 'No major code found',
            'description' => '',
            'sub_title' => 'No sub code found',
            'sub_description' => ''
        );
        $formatted_code = $this->format_status_code($code);
        if ('' === $formatted_code['code']) {
            $return['title'] = 'Could not parse code';
            $return['sub_title'] = 'Could not parse code';
        } else {
            $arr = explode('.', $formatted_code['code']);
            $return['formatted_code_code'] = $formatted_code['code'];
            $return['formatted_code_text'] = $formatted_code['text'];
            if (true === isset($arr[0])) {
                $return['major_code'] = $arr[0];
                if (true === isset($code_classes[$arr[0]])) {
                    if (true === isset($code_classes[$arr[0]]['title'])) {
                        $return['title'] = $code_classes[$arr[0]]['title'];
                    } else {
                        $return['title']
                            = 'No title available for major code: ' . $arr[0];
                    }
                    if (true === isset($code_classes[$arr[0]]['descr'])) {
                        $return['description']
                            = $code_classes[$arr[0]]['descr'];
                    }
                } else {
                    $return['title']
                        = 'Unrecognised major code: ' . $arr[0] . 'xxx';
                }
            }
            $sub_label = '';
            if (true === isset($arr[1]) && true === isset($arr[2])) {
                $sub_label = $arr[1] . '.' . $arr[2];
            } elseif (true === isset($arr[1])) {
                $sub_label = $arr[1];
            }
            if ('' !== $sub_label) {
                $return['sub_code'] = $sub_label;
                if (true === isset($sub_classes[$sub_label])) {
                    if (true === isset($sub_classes[$sub_label]['title'])) {
                        $return['sub_title']
                            = $sub_classes[$sub_label]['title'];
                    } else {
                        $return['sub_title']
                            = 'No sub title available for sub code: '
                            . $sub_label;
                    }
                    if (true === isset($sub_classes[$sub_label]['descr'])) {
                        $return['sub_description']
                            = $sub_classes[$sub_label]['descr'];
                    }
                } else {
                    $return['sub_title']
                        = 'Unrecognised sub code: ' . $sub_label;
                }
            }
        }
        return $return;
    }
}

class BounceHandlerResponses {
	
	public static $bouncelist = array(
		// use the code from the regex
		'[45]\d\d[- ]#?([45]\.\d\.\d{1,2})' => 'x',
		// use the code from the regex
		'Diagnostic[- ][Cc]ode: smtp; ?\d\d\ ([45]\.\d\.\d{1,2})' => 'x',
		// use the code from the regex
		'Status: ([45]\.\d\.\d{1,2})' => 'x',
		'not yet been delivered' => '4.2.0',
		'Message will be retried for' => '4.2.0',
		'Connection frequency limited\. http:\/\/service\.mail\.qq\.com' => '4.2.0',
		// .DE "mailbox full"
		'Benutzer hat zuviele Mails auf dem Server' => '4.2.2',
		'exceeded storage allocation' => '4.2.2',
		'Mailbox full' => '4.2.2',
		'mailbox is full' => '4.2.2', // BH
		'Mailbox quota usage exceeded' => '4.2.2', // BH
		'Mailbox size limit exceeded' => '4.2.2',
		'over ?quota' => '4.2.2',
		'quota exceeded' => '4.2.2',
		'Quota violation' => '4.2.2',
		'User has exhausted allowed storage space' => '4.2.2',
		'User has too many messages on the server' => '4.2.2',
		'User mailbox exceeds allowed size' => '4.2.2',
		'mailfolder is full' => '4.2.2',
		'user has Exceeded' => '4.2.2',
		'not enough storage space' => '4.2.2',
		/**
		 * SB: 4.3.2 is a more generic 'defer'; Kanon added.
		 * From Symantec_AntiVirus_for_SMTP_Gateways@uqam.ca I'm not sure why
		 * Symantec delayed this message, but x.2.x means something to do with the
		 * mailbox, which seemed appropriate. x.5.x (protocol) or x.7.x (security)
		 * also seem possibly appropriate. It seems a lot of times its x.5.x when
		 * it seems to me it should be x.7.x, so maybe x.5.x is standard when mail
		 * is rejected due to spam-like characteristics instead of x.7.x like I
		 * think it should be.
		 **/
		'Delivery attempts will continue to be made for' => '4.3.2',
		'delivery temporarily suspended' => '4.3.2',
		'Greylisted for 5 minutes' => '4.3.2',
		'Greylisting in action' => '4.3.2',
		'Server busy' => '4.3.2',
		'server too busy' => '4.3.2',
		'system load is too high' => '4.3.2',
		'temporarily deferred' => '4.3.2',
		'temporarily unavailable' => '4.3.2',
		'Throttling' => '4.3.2',
		'too busy to accept mail' => '4.3.2',
		'too many connections' => '4.3.2',
		'too many sessions' => '4.3.2',
		'Too much load' => '4.3.2',
		'try again later' => '4.3.2',
		'Try later' => '4.3.2',
		'retry timeout exceeded' => '4.4.7',
		'queue too long' => '4.4.7',
		'554 delivery error:' => '5.1.1',
		// SB: Yahoo/rogers.com generic delivery failure (see also OU-00)
		'account is unavailable' => '5.1.1',
		'Account not found' => '5.1.1',
		'Address invalid' => '5.1.1',
		'Address is unknown' => '5.1.1',
		'Address unknown' => '5.1.1',
		'Addressee unknown' => '5.1.1',
		'ADDRESS_NOT_FOUND' => '5.1.1',
		'bad address' => '5.1.1',
		'Bad destination mailbox address' => '5.1.1',
		// .IT "user unknown"
		'destin. Sconosciuto' => '5.1.1',
		// .IT "invalid"
		'Destinatario errato' => '5.1.1',
		'Destinatario sconosciuto o mailbox disatttivata' => '5.1.1',
		// .IT "unknown /disabled"
		'does not exist' => '5.1.1',
		'Email Address was not found' => '5.1.1',
		'Excessive userid unknowns' => '5.1.1',
		// .IT "no user"
		'Indirizzo inesistente' => '5.1.1',
		'Invalid account' => '5.1.1',
		'invalid address' => '5.1.1',
		'Invalid or unknown virtual user' => '5.1.1',
		'Invalid mailbox' => '5.1.1',
		'Invalid recipient' => '5.1.1',
		'Mailbox not found' => '5.1.1',
		'nie istnieje' => '5.1.1',
		// .PL "does not exist"
		'Nie ma takiego konta' => '5.1.1', // .PL "no such account"
		'No mail box available for this user' => '5.1.1',
		'no mailbox here' => '5.1.1',
		'No one with that email address here' => '5.1.1',
		'no such address' => '5.1.1',
		'no such email address' => '5.1.1',
		'No such mail drop defined' => '5.1.1',
		'No such mailbox' => '5.1.1',
		'No such person at this address' => '5.1.1',
		'no such recipient' => '5.1.1',
		'No such user' => '5.1.1',
		'not a known user' => '5.1.1',
		'not a valid mailbox' => '5.1.1',
		'not a valid user' => '5.1.1',
		'not available' => '5.1.1',
		'not exists' => '5.1.1',
		'Recipient address rejected' => '5.1.1',
		'Recipient not allowed' => '5.1.1',
		'Recipient not found' => '5.1.1',
		'recipient rejected' => '5.1.1',
		'Recipient unknown' => '5.1.1',
		'server doesn\'t handle mail for that user' => '5.1.1',
		'This account is disabled' => '5.1.1',
		'This address no longer accepts mail' => '5.1.1',
		'This email address is not known to this system' => '5.1.1',
		'Unknown account' => '5.1.1',
		'unknown address or alias' => '5.1.1',
		'Unknown email address' => '5.1.1',
		'Unknown local part' => '5.1.1',
		'unknown or illegal alias' => '5.1.1',
		'unknown or illegal user' => '5.1.1',
		'Unknown recipient' => '5.1.1',
		'unknown user' => '5.1.1',
		'user disabled' => '5.1.1', 'User doesn\'t exist in this server' => '5.1.1',
		'user invalid' => '5.1.1',
		'User is suspended' => '5.1.1',
		'User is unknown' => '5.1.1',
		'User not found' => '5.1.1',
		'User not known' => '5.1.1',
		'User unknown' => '5.1.1',
		'valid RCPT command must precede DATA' => '5.1.1',
		'was not found in LDAP server' => '5.1.1',
		'We are sorry but the address is invalid' => '5.1.1',
		'Unable to find alias user' => '5.1.1',
		'domain isn\'t in my list of allowed rcpthosts' => '5.1.2',
		'Esta casilla ha expirado por falta de uso' => '5.1.2', // BH ES:expired
		'host ?name is unknown' => '5.1.2',
		'no relaying allowed' => '5.1.2',
		'no such domain' => '5.1.2',
		'not our customer' => '5.1.2',
		'relay not permitted' => '5.1.2',
		'Relay access denied' => '5.1.2',
		'relaying denied' => '5.1.2',
		'Relaying not allowed' => '5.1.2',
		'This system is not configured to relay mail' => '5.1.2',
		'Unable to relay' => '5.1.2',
		'unrouteable mail domain' => '5.1.2', // BH
		'we do not relay' => '5.1.2',
		'Old address no longer valid' => '5.1.6',
		'recipient no longer on server' => '5.1.6',
		'Sender address rejected' => '5.1.8',
		'exceeded the rate limit' => '5.2.0',
		'Local Policy Violation' => '5.2.0',
		'Mailbox currently suspended' => '5.2.0',
		'mailbox unavailable' => '5.2.0',
		'mail can not be delivered' => '5.2.0',
		'Delivery failed' => '5.2.0',
		'mail couldn\'t be delivered' => '5.2.0',
		'The account or domain may not exist' => '5.2.0',
		// I guess.... seems like 5.1.1, 5.1.2, or 5.4.4 would fit too, but 5.2.0
		// seemed most generic
		'Account disabled' => '5.2.1',
		'account has been disabled' => '5.2.1',
		'Account Inactive' => '5.2.1',
		'Adressat unbekannt oder Mailbox deaktiviert' => '5.2.1',
		'Destinataire inconnu ou boite aux lettres desactivee' => '5.2.1',
		// .FR disabled
		'mail is not currently being accepted for this mailbox' => '5.2.1',
		'El usuario esta en estado: inactivo' => '5.2.1', // .IT inactive
		'email account that you tried to reach is disabled' => '5.2.1',
		'inactive user' => '5.2.1',
		'Mailbox disabled for this recipient' => '5.2.1',
		'mailbox has been blocked due to inactivity' => '5.2.1',
		'mailbox is currently unavailable' => '5.2.1',
		'Mailbox is disabled' => '5.2.1',
		'Mailbox is inactive' => '5.2.1',
		'Mailbox Locked or Suspended' => '5.2.1',
		'mailbox temporarily disabled' => '5.2.1',
		'Podane konto jest zablokowane administracyjnie lub nieaktywne' => '5.2.1',
		// .PL locked or inactive
		'Questo indirizzo e\' bloccato per inutilizzo' => '5.2.1',
		// .IT blocked/expired
		'Recipient mailbox was disabled' => '5.2.1',
		'Domain name not found' => '5.2.1',
		'couldn\'t find any host named' => '5.4.4',
		'couldn\'t find any host by that name' => '5.4.4',
		'PERM_FAILURE: DNS Error' => '5.4.4', // SB: Routing failure
		'Temporary lookup failure' => '5.4.4',
		'unrouteable address' => '5.4.4',
		'can\'t connect to' => '5.4.4',
		'Too many hops' => '5.4.6',
		'Requested action aborted' => '5.5.0',
		'rejecting password protected file attachment' => '5.6.2',
		// RFC "Conversion required and prohibited"
		'550 OU-00' => '5.7.1',
		// SB hotmail returns a OU-001 if you're on their blocklist
		'550 SC-00' => '5.7.1',
		// SB hotmail returns a SC-00x if you're on their blocklist
		'550 DY-00' => '5.7.1',
		// SB hotmail returns a DY-00x if you're a dynamic IP
		'554 denied' => '5.7.1',
		'You have been blocked by the recipient' => '5.7.1',
		'requires that you verify' => '5.7.1',
		'Access denied' => '5.7.1',
		'Administrative prohibition - unable to validate recipient' => '5.7.1',
		'Blacklisted' => '5.7.1',
		'blocke?d? for spam' => '5.7.1',
		'conection refused' => '5.7.1',
		'Connection refused due to abuse' => '5.7.1',
		'dial-up or dynamic-ip denied' => '5.7.1',
		'Domain has received too many bounces' => '5.7.1',
		'failed several antispam checks' => '5.7.1',
		'found in a DNS blacklist' => '5.7.1',
		'IPs blocked' => '5.7.1',
		'is blocked by' => '5.7.1',
		'Mail Refused' => '5.7.1',
		'Message does not pass DomainKeys' => '5.7.1',
		'Message looks like spam' => '5.7.1',
		'Message refused by' => '5.7.1',
		'not allowed access from your location' => '5.7.1',
		'permanently deferred' => '5.7.1',
		'Rejected by policy' => '5.7.1',
		'rejected by Windows Live Hotmail for policy reasons' => '5.7.1',
		'Rejected for policy reasons' => '5.7.1',
		'Rejecting banned content' => '5.7.1',
		'Sorry, looks like spam' => '5.7.1',
		'spam message discarded' => '5.7.1',
		'Too many spams from your IP' => '5.7.1',
		'TRANSACTION FAILED' => '5.7.1',
		'Transaction rejected' => '5.7.1',
		'Wiadomosc zostala odrzucona przez system antyspamowy' => '5.7.1',
		// .PL rejected as spam
		'Your message was declared Spam' => '5.7.1'
	);

	// triggers for autoresponders
	public static $autorespondlist = array(
		'auto:',
		'^away from.{0,15}office',
		'^.?auto(mated|matic).{0,5}(reply|response)',
		'^out.?of (the )?office',
		'^(I am|I\'m|I will).{0,15}\s(away|on vacation|on leave|out of office|'.
		  'out of the office)',
		'^Thank you for your e-?mail',
		'^This is an automated',
		'^Vacation.{0,10}(alert|reply|response)',
		'^Yahoo! auto response',
		// sino.com,  163.com  UTF8 encoded
		"\350\207\252\345\212\250\345\233\236\345\244\215"
	);

	// trigger subject lines for bounces
	public static $bouncesubj = array(
		'deletver reports about your e?mail',
		'delivery errors',
		'delivery failure',
		'delivery has failed',
		'delivery notification',
		'delivery problem',
		'delivery reports about your email',
		'delivery status notif',
		'failure delivery',
		'failure notice',
		'mail delivery fail',            // catches failure and failed
		'mail delivery system',
		'mailserver notification',
		'mail status report',
		'mail system error',
		'mail transaction failed',
		'mdaemon notification',
		'message delayed',
		'nondeliverable mail',
		'Non[_ ]remis[_ ]',            // fr
		'No[_ ]se[_ ]puede[_ ]entregar',    // es
		'Onbestelbaar',                // nl
		'returned e?mail',
		'returned to sender',
		'returning message to sender',
		'spam eater',
		'undeliverable',
		'undelivered mail',
		'warning: message',
	);
}

class BounceHandlerStatusCodes {
	
	public static getClasses() {
		
		$status_code_classes = [];
			
		// [RFC3463] (Standards track)
		$status_code_classes['2']['title']="Success";
		$status_code_classes['2']['descr']
			= "Success specifies that the DSN is reporting a positive delivery action. ".
			   "Detail sub-codes may provide notification of transformations required fo".
			   "r delivery.";

		// [RFC3463] (Standards track)
		$status_code_classes['4']['title']="Persistent Transient Failure";
		$status_code_classes['4']['descr']
			= "A persistent transient failure is one in which the message as sent is va".
			   "lid, but persistence of some temporary condition has caused abandonment ".
			   "or delay of attempts to send the message. If this code accompanies a del".
			   "ivery failure report, sending in the future may be successful.";

		// [RFC3463] (Standards track)
		$status_code_classes['5']['title']="Permanent Failure";
		$status_code_classes['5']['descr']
			= "A permanent failure is one which is not likely to be resolved by resendi".
			   "ng the message in the current form. Some change to the message or the de".
			   "stination must be made for successful delivery.";
		
		return $status_code_classes;
	}
	
	public static getSubClasses() {
		
		$status_code_subclasses = [];
		
		// [RFC3463] (Standards Track)
		$status_code_subclasses['0.0']['title']="Other undefined Status";
		$status_code_subclasses['0.0']['descr']
			= "Other undefined status is the only undefined error code. It should be us".
			   "ed for all errors for which only the class of the error is known.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.0']['title']="Other address status";
		$status_code_subclasses['1.0']['descr']
			= "Something about the address specified in the message caused this DSN.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.1']['title']="Bad destination mailbox address";
		$status_code_subclasses['1.1']['descr']
			= "The mailbox specified in the address does not exist. For Internet mail n".
			   "ames, this means the address portion to the left of the \"@\" sign is in".
			   "valid. This code is only useful for permanent failures.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.2']['title']="Bad destination system address";
		$status_code_subclasses['1.2']['descr']
			= "The destination system specified in the address does not exist or is inc".
			   "apable of accepting mail. For Internet mail names, this means the addres".
			   "s portion to the right of the \"@\" is invalid for mail. This code is on".
			   "ly useful for permanent failures.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.3']['title']
			= "Bad destination mailbox address syntax";

		$status_code_subclasses['1.3']['descr']
			= "The destination address was syntactically invalid. This can apply to any".
			   " field in the address. This code is only useful for permanent failures.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.4']['title']
			= "Destination mailbox address ambiguous";

		$status_code_subclasses['1.4']['descr']
			= "The mailbox address as specified matches one or more recipients on the d".
			   "estination system. This may result if a heuristic address mapping algori".
			   "thm is used to map the specified address to a local mailbox name.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.5']['title']="Destination address valid";
		$status_code_subclasses['1.5']['descr']
			= "This mailbox address as specified was valid. This status code should be ".
			   "used for positive delivery reports.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.6']['title']
			= "Destination mailbox has moved, No forwarding address";

		$status_code_subclasses['1.6']['descr']
			= "The mailbox address provided was at one time valid, but mail is no longe".
			   "r being accepted for that address. This code is only useful for permanen".
			   "t failures.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.7']['title']
			= "Bad sender's mailbox address syntax";

		$status_code_subclasses['1.7']['descr']
			= "The sender's address was syntactically invalid. This can apply to any fi".
			   "eld in the address.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['1.8']['title']="Bad sender's system address";
		$status_code_subclasses['1.8']['descr']
			= "The sender's system specified in the address does not exist or is incapa".
			   "ble of accepting return mail. For domain names, this means the address p".
			   "ortion to the right of the \"@\" is invalid for mail.";

		// [RFC3886] (Standards Track)
		$status_code_subclasses['1.9']['title']
			= "Message relayed to non-compliant mailer";

		$status_code_subclasses['1.9']['descr']
			= "The mailbox address specified was valid, but the message has been relaye".
			   "d to a system that does not speak this protocol; no further information ".
			   "can be provided.";

		// [RFC-ietf-appsawg-nullmx-08] (Standards Track)
		$status_code_subclasses['1.10']['title']="Recipient address has null MX";
		$status_code_subclasses['1.10']['descr']
			= "This status code is returned when the associated address is marked as in".
			   "valid using a null MX.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['2.0']['title']
			= "Other or undefined mailbox status";

		$status_code_subclasses['2.0']['descr']
			= "The mailbox exists, but something about the destination mailbox has caus".
			   "ed the sending of this DSN.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['2.1']['title']
			= "Mailbox disabled, not accepting messages";

		$status_code_subclasses['2.1']['descr']
			= "The mailbox exists, but is not accepting messages. This may be a permane".
			   "nt error if the mailbox will never be re-enabled or a transient error if".
			   " the mailbox is only temporarily disabled.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['2.2']['title']="Mailbox full";
		$status_code_subclasses['2.2']['descr']
			= "The mailbox is full because the user has exceeded a per-mailbox administ".
			   "rative quota or physical capacity. The general semantics implies that th".
			   "e recipient can delete messages to make more space available. This code ".
			   "should be used as a persistent transient failure.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['2.3']['title']
			= "Message length exceeds administrative limit";

		$status_code_subclasses['2.3']['descr']
			= "A per-mailbox administrative message length limit has been exceeded. Thi".
			   "s status code should be used when the per-mailbox message length limit i".
			   "s less than the general system limit. This code should be used as a perm".
			   "anent failure.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['2.4']['title']="Mailing list expansion problem";
		$status_code_subclasses['2.4']['descr']
			= "The mailbox is a mailing list address and the mailing list was unable to".
			   " be expanded. This code may represent a permanent failure or a persisten".
			   "t transient failure.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['3.0']['title']
			= "Other or undefined mail system status";

		$status_code_subclasses['3.0']['descr']
			= "The destination system exists and normally accepts mail, but something a".
			   "bout the system has caused the generation of this DSN.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['3.1']['title']="Mail system full";
		$status_code_subclasses['3.1']['descr']
			= "Mail system storage has been exceeded. The general semantics imply that ".
			   "the individual recipient may not be able to delete material to make room".
			   " for additional messages. This is useful only as a persistent transient ".
			   "error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['3.2']['title']
			= "System not accepting network messages";

		$status_code_subclasses['3.2']['descr']
			= "The host on which the mailbox is resident is not accepting messages. Exa".
			   "mples of such conditions include an immanent shutdown, excessive load, o".
			   "r system maintenance. This is useful for both permanent and persistent t".
			   "ransient errors.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['3.3']['title']
			= "System not capable of selected features";

		$status_code_subclasses['3.3']['descr']
			= "Selected features specified for the message are not supported by the des".
			   "tination system. This can occur in gateways when features from one domai".
			   "n cannot be mapped onto the supported feature in another.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['3.4']['title']="Message too big for system";
		$status_code_subclasses['3.4']['descr']
			= "The message is larger than per-message size limit. This limit may either".
			   " be for physical or administrative reasons. This is useful only as a per".
			   "manent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['3.5']['title']="System incorrectly configured";
		$status_code_subclasses['3.5']['descr']
			= "The system is not configured in a manner that will permit it to accept t".
			   "his message.";

		// [RFC6710] (Standards Track)
		$status_code_subclasses['3.6']['title']="Requested priority was changed";
		$status_code_subclasses['3.6']['descr']
			= "The message was accepted for relay/delivery, but the requested priority ".
			   "(possibly the implied default) was not honoured. The human readable text".
			   " after the status code contains the new priority, followed by SP (space)".
			   " and explanatory human readable text.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.0']['title']
			= "Other or undefined network or routing status";

		$status_code_subclasses['4.0']['descr']
			= "Something went wrong with the networking, but it is not clear what the p".
			   "roblem is, or the problem cannot be well expressed with any of the other".
			   " provided detail codes.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.1']['title']="No answer from host";
		$status_code_subclasses['4.1']['descr']
			= "The outbound connection attempt was not answered, because either the rem".
			   "ote system was busy, or was unable to take a call. This is useful only a".
			   "s a persistent transient error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.2']['title']="Bad connection";
		$status_code_subclasses['4.2']['descr']
			= "The outbound connection was established, but was unable to complete the ".
			   "message transaction, either because of time-out, or inadequate connectio".
			   "n quality. This is useful only as a persistent transient error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.3']['title']="Directory server failure";
		$status_code_subclasses['4.3']['descr']
			= "The network system was unable to forward the message, because a director".
			   "y server was unavailable. This is useful only as a persistent transient ".
			   "error. The inability to connect to an Internet DNS server is one example".
			   " of the directory server failure error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.4']['title']="Unable to route";
		$status_code_subclasses['4.4']['descr']
			= "The mail system was unable to determine the next hop for the message bec".
			   "ause the necessary routing information was unavailable from the director".
			   "y server. This is useful for both permanent and persistent transient err".
			   "ors. A DNS lookup returning only an SOA (Start of Administration) record".
			   " for a domain name is one example of the unable to route error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.5']['title']="Mail system congestion";
		$status_code_subclasses['4.5']['descr']
			= "The mail system was unable to deliver the message because the mail syste".
			   "m was congested. This is useful only as a persistent transient error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.6']['title']="Routing loop detected";
		$status_code_subclasses['4.6']['descr']
			= "A routing loop caused the message to be forwarded too many times, either".
			   " because of incorrect routing tables or a user- forwarding loop. This is".
			   " useful only as a persistent transient error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['4.7']['title']="Delivery time expired";
		$status_code_subclasses['4.7']['descr']
			= "The message was considered too old by the rejecting system, either becau".
			   "se it remained on that host too long or because the time-to-live value s".
			   "pecified by the sender of the message was exceeded. If possible, the cod".
			   "e for the actual problem found when delivery was attempted should be ret".
			   "urned rather than this code.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['5.0']['title']
			= "Other or undefined protocol status";

		$status_code_subclasses['5.0']['descr']
			= "Something was wrong with the protocol necessary to deliver the message t".
			   "o the next hop and the problem cannot be well expressed with any of the ".
			   "other provided detail codes.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['5.1']['title']="Invalid command";
		$status_code_subclasses['5.1']['descr']
			= "A mail transaction protocol command was issued which was either out of s".
			   "equence or unsupported. This is useful only as a permanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['5.2']['title']="Syntax error";
		$status_code_subclasses['5.2']['descr']
			= "A mail transaction protocol command was issued which could not be interp".
			   "reted, either because the syntax was wrong or the command is unrecognize".
			   "d. This is useful only as a permanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['5.3']['title']="Too many recipients";
		$status_code_subclasses['5.3']['descr']
			= "More recipients were specified for the message than could have been deli".
			   "vered by the protocol. This error should normally result in the segmenta".
			   "tion of the message into two, the remainder of the recipients to be deli".
			   "vered on a subsequent delivery attempt. It is included in this list in t".
			   "he event that such segmentation is not possible.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['5.4']['title']="Invalid command arguments";
		$status_code_subclasses['5.4']['descr']
			= "A valid mail transaction protocol command was issued with invalid argume".
			   "nts, either because the arguments were out of range or represented unrec".
			   "ognized features. This is useful only as a permanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['5.5']['title']="Wrong protocol version";
		$status_code_subclasses['5.5']['descr']
			= "A protocol version mis-match existed which could not be automatically re".
			   "solved by the communicating parties.";

		// [RFC4954] (Standards Track)
		$status_code_subclasses['5.6']['title']
			= "Authentication Exchange line is too long";

		$status_code_subclasses['5.6']['descr']
			= "This enhanced status code SHOULD be returned when the server fails the A".
			   "UTH command due to the client sending a [BASE64] response which is longe".
			   "r than the maximum buffer size available for the currently selected SASL".
			   " mechanism. This is useful for both permanent and persistent transient e".
			   "rrors.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['6.0']['title']="Other or undefined media error";
		$status_code_subclasses['6.0']['descr']
			= "Something about the content of a message caused it to be considered unde".
			   "liverable and the problem cannot be well expressed with any of the other".
			   " provided detail codes.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['6.1']['title']="Media not supported";
		$status_code_subclasses['6.1']['descr']
			= "The media of the message is not supported by either the delivery protoco".
			   "l or the next system in the forwarding path. This is useful only as a pe".
			   "rmanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['6.2']['title']
			= "Conversion required and prohibited";

		$status_code_subclasses['6.2']['descr']
			= "The content of the message must be converted before it can be delivered ".
			   "and such conversion is not permitted. Such prohibitions may be the expre".
			   "ssion of the sender in the message itself or the policy of the sending h".
			   "ost.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['6.3']['title']
			= "Conversion required but not supported";

		$status_code_subclasses['6.3']['descr']
			= "The message content must be converted in order to be forwarded but such ".
			   "conversion is not possible or is not practical by a host in the forwardi".
			   "ng path. This condition may result when an ESMTP gateway supports 8bit t".
			   "ransport but is not able to downgrade the message to 7 bit as required f".
			   "or the next hop.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['6.4']['title']="Conversion with loss performed";
		$status_code_subclasses['6.4']['descr']
			= "This is a warning sent to the sender when message delivery was successfu".
			   "lly but when the delivery required a conversion in which some data was l".
			   "ost. This may also be a permanent error if the sender has indicated that".
			   " conversion with loss is prohibited for the message.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['6.5']['title']="Conversion Failed";
		$status_code_subclasses['6.5']['descr']
			= "A conversion was required but was unsuccessful. This may be useful as a ".
			   "permanent or persistent temporary notification.";

		// [RFC4468] (Standards Track)
		$status_code_subclasses['6.6']['title']="Message content not available";
		$status_code_subclasses['6.6']['descr']
			= "The message content could not be fetched from a remote system. This may ".
			   "be useful as a permanent or persistent temporary notification.";

		// [RFC6531] (Standards track)
		$status_code_subclasses['6.7']['title']
			= "Non-ASCII addresses not permitted for that sender/recipient";

		$status_code_subclasses['6.7']['descr']
			= "This indicates the reception of a MAIL or RCPT command that non-ASCII ad".
			   "dresses are not permitted";

		// [RFC6531] (Standards track)
		$status_code_subclasses['6.8']['title']
			= "UTF-8 string reply is required, but not permitted by the SMTP client";

		$status_code_subclasses['6.8']['descr']
			= "This indicates that a reply containing a UTF-8 string is required to sho".
			   "w the mailbox name, but that form of response is not permitted by the SM".
			   "TP client.";

		// [RFC6531] (Standards track)
		$status_code_subclasses['6.9']['title']
			= "UTF-8 header message cannot be transferred to one or more recipients, so".
			   " the message must be rejected";

		$status_code_subclasses['6.9']['descr']
			= "This indicates that transaction failed after the final \".\" of the DATA".
			   " command.";

		// [RFC6531] (Standards track)
		$status_code_subclasses['6.10']['title']="";
		$status_code_subclasses['6.10']['descr']
			= "This is a duplicate of X.6.8 and is thus deprecated.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.0']['title']
			= "Other or undefined security status";

		$status_code_subclasses['7.0']['descr']
			= "Something related to security caused the message to be returned, and the".
			   " problem cannot be well expressed with any of the other provided detail ".
			   "codes. This status code may also be used when the condition cannot be fu".
			   "rther described because of security policies in force.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.1']['title']
			= "Delivery not authorized, message refused";

		$status_code_subclasses['7.1']['descr']
			= "The sender is not authorized to send to the destination. This can be the".
			   " result of per-host or per-recipient filtering. This memo does not discu".
			   "ss the merits of any such filtering, but provides a mechanism to report ".
			   "such. This is useful only as a permanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.2']['title']
			= "Mailing list expansion prohibited";

		$status_code_subclasses['7.2']['descr']
			= "The sender is not authorized to send a message to the intended mailing l".
			   "ist. This is useful only as a permanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.3']['title']
			= "Security conversion required but not possible";

		$status_code_subclasses['7.3']['descr']
			= "A conversion from one secure messaging protocol to another was required ".
			   "for delivery and such conversion was not possible. This is useful only a".
			   "s a permanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.4']['title']="Security features not supported";
		$status_code_subclasses['7.4']['descr']
			= "A message contained security features such as secure authentication that".
			   " could not be supported on the delivery protocol. This is useful only as".
			   " a permanent error.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.5']['title']="Cryptographic failure";
		$status_code_subclasses['7.5']['descr']
			= "A transport system otherwise authorized to validate or decrypt a message".
			   " in transport was unable to do so because necessary information such as ".
			   "key was not available or such information was invalid.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.6']['title']
			= "Cryptographic algorithm not supported";

		$status_code_subclasses['7.6']['descr']
			= "A transport system otherwise authorized to validate or decrypt a message".
			   " was unable to do so because the necessary algorithm was not supported.";

		// [RFC3463] (Standards Track)
		$status_code_subclasses['7.7']['title']="Message integrity failure";
		$status_code_subclasses['7.7']['descr']
			= "A transport system otherwise authorized to validate a message was unable".
			   " to do so because the message was corrupted or altered. This may be usef".
			   "ul as a permanent, transient persistent, or successful delivery code.";

		// [RFC4954] (Standards Track)
		$status_code_subclasses['7.8']['title']
			= "Authentication credentials invalid";

		$status_code_subclasses['7.8']['descr']
			= "This response to the AUTH command indicates that the authentication fail".
			   "ed due to invalid or insufficient authentication credentials. In this ca".
			   "se, the client SHOULD ask the user to supply new credentials (such as by".
			   " presenting a password dialog box).";

		// [RFC4954] (Standards Track)
		$status_code_subclasses['7.9']['title']
			= "Authentication mechanism is too weak";

		$status_code_subclasses['7.9']['descr']
			= "This response to the AUTH command indicates that the selected authentica".
			   "tion mechanism is weaker than server policy permits for that user. The c".
			   "lient SHOULD retry with a new authentication mechanism.";

		// [RFC5248] (Best current practice)
		$status_code_subclasses['7.10']['title']="Encryption Needed";
		$status_code_subclasses['7.10']['descr']
			= "This indicates that external strong privacy layer is needed in order to ".
			   "use the requested authentication mechanism. This is primarily intended f".
			   "or use with clear text authentication mechanisms. A client which receive".
			   "s this may activate a security layer such as TLS prior to authenticating".
			   ", or attempt to use a stronger mechanism.";

		// [RFC4954] (Standards Track)
		$status_code_subclasses['7.11']['title']
			= "Encryption required for requested authentication mechanism";

		$status_code_subclasses['7.11']['descr']
			= "This response to the AUTH command indicates that the selected authentica".
			   "tion mechanism may only be used when the underlying SMTP connection is e".
			   "ncrypted. Note that this response code is documented here for historical".
			   " purposes only. Modern implementations SHOULD NOT advertise mechanisms t".
			   "hat are not permitted due to lack of encryption, unless an encryption la".
			   "yer of sufficient strength is currently being employed.";

		// [RFC4954] (Standards Track)
		$status_code_subclasses['7.12']['title']
			= "A password transition is needed";

		$status_code_subclasses['7.12']['descr']
			= "This response to the AUTH command indicates that the user needs to trans".
			   "ition to the selected authentication mechanism. This is typically done b".
			   "y authenticating once using the [PLAIN] authentication mechanism. The se".
			   "lected mechanism SHOULD then work for authentications in subsequent sess".
			   "ions.";

		// [RFC5248] (Best current practice)
		$status_code_subclasses['7.13']['title']="User Account Disabled";
		$status_code_subclasses['7.13']['descr']
			= "Sometimes a system administrator will have to disable a user's account (".
			   "e.g., due to lack of payment, abuse, evidence of a break-in attempt, etc".
			   "). This error code occurs after a successful authentication to a disable".
			   "d account. This informs the client that the failure is permanent until t".
			   "he user contacts their system administrator to get the account re-enable".
			   "d. It differs from a generic authentication failure where the client's b".
			   "est option is to present the passphrase entry dialog in case the user si".
			   "mply mistyped their passphrase.";

		// [RFC5248] (Best current practice)
		$status_code_subclasses['7.14']['title']="Trust relationship required";
		$status_code_subclasses['7.14']['descr']
			= "The submission server requires a configured trust relationship with a th".
			   "ird-party server in order to access the message content. This value repl".
			   "aces the prior use of X.7.8 for this error condition. thereby updating [".
			   "RFC4468].";

		// [RFC6710] (Standards Track)
		$status_code_subclasses['7.15']['title']="Priority Level is too low";
		$status_code_subclasses['7.15']['descr']
			= "The specified priority level is below the lowest priority acceptable for".
			   " the receiving SMTP server. This condition might be temporary, for examp".
			   "le the server is operating in a mode where only higher priority messages".
			   " are accepted for transfer and delivery, while lower priority messages a".
			   "re rejected.";

		// [RFC6710] (Standards Track)
		$status_code_subclasses['7.16']['title']
			= "Message is too big for the specified priority";

		$status_code_subclasses['7.16']['descr']
			= "The message is too big for the specified priority. This condition might ".
			   "be temporary, for example the server is operating in a mode where only h".
			   "igher priority messages below certain size are accepted for transfer and".
			   " delivery.";

		// [RFC7293] (Standards Track)
		$status_code_subclasses['7.17']['title']="Mailbox owner has changed";
		$status_code_subclasses['7.17']['descr']
			= "This status code is returned when a message is received with a Require-R".
			   "ecipient-Valid-Since field or RRVS extension and the receiving system is".
			   " able to determine that the intended recipient mailbox has not been unde".
			   "r continuous ownership since the specified date-time.";

		// [RFC7293] (Standards Track)
		$status_code_subclasses['7.18']['title']="Domain owner has changed";
		$status_code_subclasses['7.18']['descr']
			= "This status code is returned when a message is received with a Require-R".
			   "ecipient-Valid-Since field or RRVS extension and the receiving system wi".
			   "shes to disclose that the owner of the domain name of the recipient has ".
			   "changed since the specified date-time.";

		// [RFC7293] (Standards Track)
		$status_code_subclasses['7.19']['title']="RRVS test cannot be completed";
		$status_code_subclasses['7.19']['descr']
			= "This status code is returned when a message is received with a Require-R".
			   "ecipient-Valid-Since field or RRVS extension and the receiving system ca".
			   "nnot complete the requested evaluation because the required timestamp wa".
			   "s not recorded. The message originator needs to decide whether to reissu".
			   "e the message without RRVS protection.";

		// [RFC7372] (Standards Track); [RFC6376] (Standards Track)
		$status_code_subclasses['7.20']['title']
			= "No passing DKIM signature found";

		$status_code_subclasses['7.20']['descr']
			= "This status code is returned when a message did not contain any passing ".
			   "DKIM signatures. (This violates the advice of Section 6.1 of [RFC6376].)";

		// [RFC7372] (Standards Track); [RFC6376] (Standards Track)
		$status_code_subclasses['7.21']['title']
			= "No acceptable DKIM signature found";

		$status_code_subclasses['7.21']['descr']
			= "This status code is returned when a message contains one or more passing".
			   " DKIM signatures, but none are acceptable. (This violates the advice of ".
			   "Section 6.1 of [RFC6376].)";

		// [RFC7372] (Standards Track); [RFC6376] (Standards Track)
		$status_code_subclasses['7.22']['title']
			= "No valid author-matched DKIM signature found";

		$status_code_subclasses['7.22']['descr']
			= "This status code is returned when a message contains one or more passing".
			   " DKIM signatures, but none are acceptable because none have an identifie".
			   "r(s) that matches the author address(es) found in the From header field.".
			   " This is a special case of X.7.21. (This violates the advice of Section ".
			   "6.1 of [RFC6376].)";

		// [RFC7372] (Standards Track); [RFC7208] (Standards Track)
		$status_code_subclasses['7.23']['title']="SPF validation failed";
		$status_code_subclasses['7.23']['descr']
			= "This status code is returned when a message completed an SPF check that ".
			   "produced a \"fail\" result, contrary to local policy requirements. Used ".
			   "in place of 5.7.1 as described in Section 8.4 of [RFC7208].";

		// [RFC7372] (Standards Track); [RFC7208] (Standards Track)
		$status_code_subclasses['7.24']['title']="SPF validation error";
		$status_code_subclasses['7.24']['descr']
			= "This status code is returned when evaluation of SPF relative to an arriv".
			   "ing message resulted in an error. Used in place of 4.4.3 or 5.5.2 as des".
			   "cribed in Sections 8.6 and 8.7 of [RFC7208].";

		// [RFC7372] (Standards Track); [Section 3 of RFC7001] (Standards Track)
		$status_code_subclasses['7.25']['title']="Reverse DNS validation failed";
		$status_code_subclasses['7.25']['descr']
			= "This status code is returned when an SMTP client's IP address failed a r".
			   "everse DNS validation check, contrary to local policy requirements.";

		// [RFC7372] (Standards Track)
		$status_code_subclasses['7.26']['title']
			= "Multiple authentication checks failed";

		$status_code_subclasses['7.26']['descr']
			= "This status code is returned when a message failed more than one message".
			   " authentication check, contrary to local policy requirements. The partic".
			   "ular mechanisms that failed are not specified.";

		// [RFC-ietf-appsawg-nullmx-08] (Standards Track)
		$status_code_subclasses['7.27']['title']="Sender address has null MX";
		$status_code_subclasses['7.27']['descr']
			= "This status code is returned when the associated sender address has a nu".
			   "ll MX, and the SMTP receiver is configured to reject mail from such send".
			   "er (e.g. because it could not return a DSN).";
		
		return $status_code_subclasses;
	}
}
