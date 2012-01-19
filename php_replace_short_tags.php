#!php-cli
<?php
/**
 * php_replace_short_tags
 * Copyright © 2010 Weirdmasters, Austin, Texas
 *
 * License:
 *   CC-BY-SA v3.0
 *   http://creativecommons.org/licenses/by-sa/3.0/
 *
 * Replace short open tags in PHP source with long open tags.
 *
 * e.g. Replace this:
 *          <?=
 *      With this:
 *          <?php echo
 *
 * USAGE:
 *
 *  php_replace_short_tags [-s|--skip-echo-tags] [-q|--quiet] [-d|--debug] [INFILE [OUTFILE]]
 *
 *  php_replace_short_tags --overwrite [-s|--skip-echo-tags] [-q|--quiet] [-d|--debug] INOUTFILE [INOUTFILE ...]
 *
 */

define('T_PSEUDO_EOF',0x7E0F7E0F);

global $argv;
$debug = FALSE;
$quiet = 0;
$overwrite = 0;
$not_echo_tags = 0;

$myname = array_shift($argv);

while(count($argv) && (substr($argv[0],0,1) == "-")) {
  $option = array_shift($argv);
  if(substr($option,0,2) != '--') {
    if(substr($option,2)) {
      array_unshift($argv,"-".substr($option,2));
    }
    $option = substr($option,0,2);
  }
  switch($option) {
    case "--":
      break 2;
    case "-h":
    case "--help":
      usage("",0);
      break;
    case "-q":
    case "--quiet":
      $quiet++;
      break;
    case "-d":
    case "--debug":
      if(!$debug) {
        $debug = TRUE;
        define('DEBUG',TRUE);
      }
      break;
    case "-s":
    case "--skip-echo-tags":
      $not_echo_tags++;
      break;
    case "--overwrite":
      $overwrite++;
      break;
    default:
      usage("Unrecognized option: \"" . $option . "\"");
  }
}
if(!$debug) {
  define('DEBUG',FALSE);
}

$filenames = $argv;
$file_count = count($filenames);
if($overwrite) {
  $file_count or
    usage("Cannot overwrite STDIN");
}
else {
  ($file_count <= 2) or
    usage("Cannot process multiple files without \"--overwrite\"");
  if ($file_count == 0) {
    $filenames = array("php://input");
  }
}

// short_open_tag setting has to be on for this to do anything
if(!ini_get('short_open_tag')) {
  ini_set('short_open_tag', true);
  if (!ini_get('short_open_tag')) {
    ($quiet) || fputs(STDERR,"WARNING: short_open_tag is disabled in php.ini\n");
  }
}


while($filenames) {
  $source_filename = array_shift($filenames);
  $source = file_get_contents($source_filename);
  if ($source === false)
    die_with_status(1,"Unable to read input file");

  $changes = replace_short_open_tags($source,!$not_echo_tags);

  if($overwrite) {
    $output_filename = $source_filename;
  }
  elseif(count($filenames)) {
    $output_filename = array_shift($filenames);
  }
  else {
    $output_filename = "php://output";
  }
  if(($changes > 0) || (!$overwrite)) {
    file_put_contents($output_filename,$source) or
      die_with_status(1,"Unable to write output file");
  }

  if (!$quiet) {
    if ($overwrite && ($file_count>1)) {
      $out_prefix = $output_filename . ": ";
    }
    else {
      $out_prefix = "";
    }
    fprintf(STDERR, "%s%u change%s\n", $out_prefix, $changes, $changes!=1?"s":"");
  }
}
exit(0);

function die_with_status($status, $message) {
  fputs(STDERR,$message);
  exit($status);
}

function usage($message,$status = 2) {
  global $myname;
  $opts = "[-s|--skip-echo-tags] [-q|--quiet] [-d|--debug] [-h|--help]";
  if($message) $message .= "\n";
  die_with_status($status,
    "USAGE:\n" .
    "  " . basename($myname) . " $opts [INFILE [OUTFILE]]\n" .
    "  " . basename($myname) . " --overwrite $opts INOUTFILE [INOUTFILE ...]\n" .
    $message);
}

function _token_name($token_id) {
  if($token_id == T_PSEUDO_EOF) {
    return "T_PSEUDO_EOF";
  }
  return token_name($token_id);
}

/**
 * replace_short_open_tags()
 *
 * INPUT
 * $source - A string containing the input source,
 *           possibly with short open tags.
 * $echo_tags - TRUE if to process <?= tags, else they
 *              are not replaced.
 *
 * OUTPUT
 * $source - A string with the short codes replaced by
 *           long codes.
 *
 * RETURN VALUE
 *         - the number of replacements made.
 *  
 * The internal parser requires that the php.ini setting
 * short_open_tag be set (On), else it will not recognize
 * short open tags and nothing will change.
 *
 * We use the same variable for input as for output to
 * allow for minimum memory consumption.
 *
 */
function replace_short_open_tags(&$source, $echo_tags = TRUE) {
  $change_count = 0;  // the number of changes we made

  $tokens = token_get_all($source);

  // A pseudo-EOF token is necessary to correctly process
  // an open tag when it's the last token, which happens
  // when it's on the last line and the last line doesn't
  // have a terminating LF. (The next higher level syntax
  // parser triggers an error if the last token is "<?php"
  // but not if it's "<?" or "<?=".)
  $tokens[] = array(T_PSEUDO_EOF,"",-1);

  $source = "";

  while(count($tokens)) {
    $token = array_shift($tokens);
    if(is_array($token)) {
      list($toktype, $toktext) = $token;
      (DEBUG) and fprintf(STDERR,"{%s:%s}\n",_token_name($toktype),$toktext);
      if ($toktype == T_OPEN_TAG) {
        // change "<?" to "<?php"
        if (($toktext == "<?") && ($tokens[0][0] != T_STRING)) {
          $toktext = "<?php";
          $change_count++;
          // Token separation is natural with "<?" but it
          // requires a separator for "<?php", so look
          // ahead to make sure a separator follows
          // and insert one if not.
          if ($tokens[0][0] != T_WHITESPACE) {
            $toktext .= " "; // serves as a token separator
          }
        }
      }
      else if($echo_tags && ($toktype == T_OPEN_TAG_WITH_ECHO)) {
        // change "<?=" to "<?php echo"
        $toktext = "<?php echo";
        $change_count++;
        // Token separation is natural with "<?=" but it
        // requires a separator for "<?php echo", so look
        // ahead to make sure a separator follows
        // and insert one if not.
        if($tokens[0][0] != T_WHITESPACE) {
          $toktext .= " "; // serves as a token separator
        }
      }
      $source .= $toktext;
    }
    else {
      // special character token
      (DEBUG) and fprintf(STDERR,"{T_CHARACTER:%s}\n",$token);
      $source .= $token;
    }
  }

  return $change_count;
}
