<?php

$tt = array(
		'f1'=>function(){echo 'F1';}
);

$tt['f1']();
return;

	function delete_event()	{ return false; }
	function destroy() { Gtk::main_quit(); }
	Gtk::rc_parse(dirname($argv[0]).'\share\themes\MS-Windows\gtk-2.0\gtkrc');
	//Callback function that displays the file name
	
$window = new GtkWindow();
//Title of the window
$window->set_title("GtkTable usage demonstration");
//Initial placement of the window 
$window->set_position(Gtk::WIN_POS_CENTER);
//Connecting the "destroy" signal
$window->connect_simple('destroy', array('Gtk', 'main_quit'));

$window->set_border_width(10);
 
/*
* Creating a new file chooser button
* Note that the second parameter dictates what action
* will be performed when the open button of the
* GtkFileChooserDialog widget is clicked
*/
$thebutton = new GtkFileChooserButton(
    'Select the File',
    Gtk::FILE_CHOOSER_ACTION_OPEN
	);

$thebutton->set_action(Gtk::FILE_CHOOSER_ACTION_SELECT_FOLDER);


 
//Creating our GtkTable
// note that the homogeneous property defaults to false.
$table = new GtkTable(3, 3, false);
 
//Let's define the spacing between columns and rows to 10 pixels
$table->set_row_spacings(3);
$table->set_col_spacings(3);
 
//Adding the table to the window
$window->add($table);
 
//Now that we have a table, let's add some widgets to it
//Note the different AttachOptions: 
// resize the window to see the effects of each
$text = new GtkEntry();
$cpLayout = new GtkHBox();
	$btnfile = new GtkButton('...');
	$btnfile->connect_simple('clicked', 'select_folder',$text);

	$cpLayout->pack_start($text,true);
	$cpLayout->pack_start($btnfile,false);
$table->attach($cpLayout, 0, 3, 0, 1);


$button1 = new GtkButton('Button 1 ');
$table->attach($button1, 0, 1, 1, 2, Gtk::SHRINK, Gtk::SHRINK, 3, 3);
 
$button2 = new GtkButton('Button 2 ');
$table->attach($button2, 1, 2, 1, 2, Gtk::FILL, Gtk::FILL, 3, 3);
 
$button3 = new GtkButton('Button 3 ');
$table->attach($button3, 2, 3, 1, 2, Gtk::FILL, Gtk::EXPAND, 3, 3);
 
//Let's add a label with information.
// We'll use it to experiment with acessing
// widgets in a GtkTable
$label = new GtkLabel(
    "Expand this window to see the difference \r\n"
    . "between the GtkAttachOptions settings."
	);
$table->attach($label, 0, 3, 2, 3, Gtk::SHRINK, Gtk::SHRINK);
$button3->connect_simple('clicked', 'select_folder',$label);
 
//Adding a button that will change the text in the label
$button4 = new GtkButton('Change label text');
 
//If you recall, we created a 3*3 table, but as we're out
// of space right now, this button will be placed on row
// 4. You can use resize(), but just attaching
// the child will cause the table to automatically change
// it's size 
$table->attach($button4, 0, 3, 3, 4, Gtk::FILL, Gtk::EXPAND, 3, 3);
$table->attach($thebutton, 0, 3, 4, 5, Gtk::FILL, Gtk::EXPAND, 3, 3);

$tv = new GtkTextView();
$tv->set_wrap_mode(Gtk::WRAP_WORD);
$table->attach($tv, 0, 3, 5, 6, Gtk::FILL, Gtk::EXPAND, 3, 3);

 
//Let's connect button4 to a function
// that changes the text of the label
$button4->connect_simple('clicked', 'change_text');
 
//This function accesses the GtkLabel and changes it's content
function change_text($ctrl)
{
	//Getting a list of the GtkTable's child widgets
	global $table;
 
	$children = $table->get_children();
	//Echoing the name of the children to the console
	foreach($children as $key => $var) {
	    echo $key.':'.$var->get_name()."\n";
	}
	echo "\n";
	//Accessing the label's text
	$current_text = $children['2']->get_text();
	//Decide which text to show
	if (substr($current_text, 0, 6) == "Expand") {
		$children['2']->set_text("Have a nice day! \r\n");
	} else {
		$children['2']->set_text(
            "Expand this window to see the difference \r\n"
            . "between the GtkAttachOptions settings."
        );
	}
	}
	
function select_folder($ctrl)
	{
        $dialog = new 
			GtkFileChooserDialog
				(
				"File Save", 
				null, 
				Gtk::FILE_CHOOSER_ACTION_SELECT_FOLDER,
				array(Gtk::STOCK_OK, Gtk::RESPONSE_OK), 
				null
				);
        $dialog->show_all();
		$code = $dialog->run();
        if ($code == Gtk::RESPONSE_OK) {
            $selected_file = $dialog->get_filename();
            echo "selected_file = $selected_file\n";
			if ($ctrl) $ctrl->set_text($selected_file);
        }
		else
			echo $code."\n";
		
		$dialog->destroy();
	}

//Make everything in the window visible
$window->show_all();
//Main loop
Gtk::main();
?>