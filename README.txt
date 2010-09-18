
  php_replace_short_tags

  Copyright © 2010 Daniel Norton, Austin, Texas

  LICENSE: CC-BY-SA v3.0
    http://creativecommons.org/licenses/by-sa/3.0/
  
  SYNOPSIS:
    Replace short open tags in PHP source with long open tags.
  
    e.g. Replace this:
           <?=
         With this:
           <?php echo
  
  USAGE:
  
   php_replace_short_tags [-s|--skip-echo-tags] [-q|--quiet] [-d|--debug] [INFILE [OUTFILE]]
  
   php_replace_short_tags --overwrite [-s|--skip-echo-tags] [-q|--quiet] [-d|--debug] INOUTFILE [INOUTFILE ...]
