<?php 
ini_set('display_errors','stderr');
$order = 1;
function clean(string $x){          //odstrani z riadku komentare a znaky \r\n
    for($i = 0;$i<strlen($x);$i++){
        if($x[$i] == '#'){
            $x = substr($x, 0, $i);
            break;
        }
    }
    $x = str_replace(array("\n", "\r"), '', $x);
    $x = trim($x);
    $x = preg_replace('/\s+/', ' ',$x);
    return $x;
}

function checklabel(string $var,int $arg){            //skontroluje ci dany retazec vyhovuje navestiu
    if(preg_match("/\A(_|-|\\$|&|%|\*|!|\?|[A-Z]|[a-z])(_|-|\\$|&|%|\*|!|\?|[A-Z]|[a-z]|[0-9])*\Z/",$var)){
        //filtracia &, <, >
        $var = str_replace('&','&amp;',$var);
        $var = str_replace('<','&lt;',$var);
        $var = str_replace('>','&gt;',$var);
        echo("\t\t<arg".$arg." type=\"label\">".$var."</arg".$arg.">\n");
    }else{
        fwrite(STDERR, "chybny argument prikazu");
        exit(23);
    }
}


function checkvar(string $var,int $arg){            //skontroluje ci dany retazec vyhovuje premennej
    if(preg_match("/\A(LF|TF|GF)@(_|-|\\$|&|%|\*|!|\?|[A-Z]|[a-z])(_|-|\\$|&|%|\*|!|\?|[A-Z]|[a-z]|[0-9])*\Z/",$var)){
        //filtracia &, <, >
        $var = str_replace('&','&amp;',$var);
        $var = str_replace('<','&lt;',$var);
        $var = str_replace('>','&gt;',$var);
        echo("\t\t<arg".$arg." type=\"var\">".$var."</arg".$arg.">\n");
    }else{
        fwrite(STDERR, "chybny argument prikazu");
        exit(23);
    }
}

function checktype(string $var, int $arg){         //skontroluje ci dany retazedc vyhovuje premennej alebo konstante
    if(preg_match("/\A((int)|(bool)|(string))\Z/",$var)){                    //premenna
        echo("\t\t<arg".$arg." type=\"type\">".$var."</arg".$arg.">\n");
    }else{
        fwrite(STDERR, "chybny argument prikazu");
        exit(23);
    }
}

function checksymb(string $var, int $arg){         //skontroluje ci dany retazedc vyhovuje premennej alebo konstante
    if(preg_match("/\A(LF|TF|GF)@(_|-|\\$|&|%|\*|!|\?|[A-Z]|[a-z])(_|-|\\$|&|%|\*|!|\?|[A-Z]|[a-z]|[0-9])*\Z/",$var)){                    //premenna
        $var = str_replace('&','&amp;',$var);
        $var = str_replace('<','&lt;',$var);
        $var = str_replace('>','&gt;',$var);
        echo("\t\t<arg".$arg." type=\"var\">".$var."</arg".$arg.">\n");
    }
    elseif(preg_match("/\Aint@(\+|-)?((0x([0-9]|[a-f]|[A-F])+)|([1-9][0-9]*)|(0[0-7]*))\Z/",$var)){                        //konstanta int
        for($i = 0;$i<strlen($var);$i++){
            if($var[$i] == '@'){
                $var = substr($var, $i+1, strlen($var)-1);
                break;
            }
        }
        echo("\t\t<arg".$arg." type=\"int\">".$var."</arg".$arg.">\n");
    }
    elseif(preg_match("/\Abool@(true|false)\Z/",$var)){                 //konstanta bool
        for($i = 0;$i<strlen($var);$i++){
            if($var[$i] == '@'){
                $var = substr($var, $i+1, strlen($var)-1);
                break;
            }
        }
        echo("\t\t<arg".$arg." type=\"bool\">".$var."</arg".$arg.">\n");
    }
    elseif(preg_match("/\Anil@nil\Z/",$var)){                 //konstanta nil
        echo("\t\t<arg".$arg." type=\"nil\">nil</arg".$arg.">\n");
    }
    elseif(preg_match("/\Astring@(([^\\x00-\\x20\\x23\\x5C])|(\\\([0-9][0-9][0-9])))*\Z/",$var)){           //konstanta string
        for($i = 0;$i<strlen($var);$i++){
            if($var[$i] == '@'){
                $var = substr($var, $i+1, strlen($var)-1);
                break;
            }
        }
        $var = str_replace('&','&amp;',$var);
        $var = str_replace('<','&lt;',$var);
        $var = str_replace('>','&gt;',$var);
        echo("\t\t<arg".$arg." type=\"string\">".$var."</arg".$arg.">\n");
    }else{
        fwrite(STDERR, "chybny argument prikazu");
        exit(23);
    }
}

if($argc == 2){
    if($argv[1] == '--help'){
        echo("Skript nacita zo standardneho vstupu zdrojovy kod v jazyku IPPcode23, skontroluje lexikalnu a syntakticku spravnost kodu a vypise na standardny vystup XML reprezentaciu programu. \n Parametre: \n \n--help - vypise tuto napovedu.\n\n");
        exit(0);
    }
    else{
        fwrite(STDERR, "neznamy argument");
        exit(10);
    }
}
if($argc > 2){
    fwrite(STDERR, "priliz vela argumentov");
    exit(10);
}


while($line = fgets(STDIN)){                    //nacitanie hlavicky
    $line = clean($line);
    $command = explode(' ',$line);
    if($command[0] == '.IPPcode23'){
        echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
        echo("<program language=\"IPPcode23\">\n");
        break;
    }elseif($command[0] == ''){
        continue;
    }else{
        fwrite(STDERR, "chybajuca hlavicka v zdrojovom kode");
        exit(21);
    }
}


while($line = fgets(STDIN)){
    $line = clean($line);
    $command = explode(' ',$line);
    switch(strtoupper($command[0])){

        case 'MOVE':
        case 'TYPE':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            if(array_key_exists(3, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'CREATEFRAME':
        case 'PUSHFRAME':
        case 'POPFRAME':
        case 'RETURN':
        case 'BREAK':
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            if(array_key_exists(1, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");    
            break;
        
        case 'DEFVAR':
            if(!array_key_exists(1, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            if(array_key_exists(2, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'CALL':
        case 'LABEL':
        case 'JUMP':
            if(!array_key_exists(1, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checklabel($command[1],1);
            if(array_key_exists(2, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;
        
        case 'PUSHS':
        case 'EXIT':
        case 'DPRINT':
            if(!array_key_exists(1, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checksymb($command[1],1);
            if(array_key_exists(2, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'POPS':
            if(!array_key_exists(1, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            if(array_key_exists(2, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;
        case 'ADD':
        case 'SUB':
        case 'IDIV':
        case 'MUL':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)|| !array_key_exists(3, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            checksymb($command[3],3);
            if(array_key_exists(4, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;
        
        case 'LT':
        case 'GT':
        case 'EQ':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)|| !array_key_exists(3, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            checksymb($command[3],3);
            if(array_key_exists(4, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'AND':
        case 'OR':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)|| !array_key_exists(3, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            checksymb($command[3],3);
            if(array_key_exists(4, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;
        
        case 'NOT':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            if(array_key_exists(3, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;
        
        case 'INT2CHAR':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            if(array_key_exists(3, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'STRI2INT':
        case 'GETCHAR':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)|| !array_key_exists(3, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            checksymb($command[3],3);
            if(array_key_exists(4, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'READ':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checktype($command[2],2);
            if(array_key_exists(3, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;
        case 'WRITE':
            if(!array_key_exists(1, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checksymb($command[1],1);
            if(array_key_exists(2, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'CONCAT':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)|| !array_key_exists(3, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            checksymb($command[3],3);
            if(array_key_exists(4, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'STRLEN':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            if(array_key_exists(3, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'SETCHAR':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)|| !array_key_exists(3, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checkvar($command[1],1);
            checksymb($command[2],2);
            checksymb($command[3],3);
            if(array_key_exists(4, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;

        case 'JUMPIFEQ':
        case 'JUMPIFNEQ':
            if(!array_key_exists(1, $command) || !array_key_exists(2, $command)|| !array_key_exists(3, $command)){
                fwrite(STDERR, "priliz malo argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t<instruction order=\"".$order."\" opcode=\"".strtoupper($command[0])."\">\n");
            $order++;
            checklabel($command[1],1);
            checksymb($command[2],2);
            checksymb($command[3],3);
            if(array_key_exists(4, $command)){
                fwrite(STDERR, "priliz vela argumentov instrukcie ".$command[0]);
                exit(23);
            }
            echo("\t</instruction>\n");
            break;
        case '':
            break;
        default:
            echo("chybny operacny kod.");
            exit(22);
    }
}
echo("</program>\n");
?> 