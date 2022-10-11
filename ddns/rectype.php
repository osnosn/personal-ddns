<?php
function rectype($typ) {
   switch($typ){
     case 1: return 'A';break;
     case 2: return 'TXT';break;
     case 3: return 'AAAA';break;
     case 4: return 'CNAME';break;
     default: return 'UNKNOWN';
   }
}
function rectype_num($typ) {
   switch($typ){
     case 'A': return 1;break;
     case 'TXT': return 2;break;
     case 'AAAA': return 3;break;
     case 'CNAME': return 4;break;
     default: return -1;
   }
}
