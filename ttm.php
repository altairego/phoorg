<?php

$window = new GtkWindow();
$window->set_size_request(400, 150);
$window->connect_simple('destroy', array('Gtk','main_quit'));
$window->add($vbox = new GtkVBox());
$accel_group = new GtkAccelGroup();
$window->add_accel_group($accel_group);

// define menu definition 
$menu_definition = array(
    '_File' => array('_New|N', '_Open|O', '_Close|C', '<hr>', '_Save|S', 'Save _As','<hr>', 'E_xit'),
    '_Edit' => array('Cu_t|X', '_Copy|C', '_Paste|V', '<hr>', 'Select _All|A', '<hr>', '_Undo|Z','_Redo|Y'),
    '_Test' => array('Test_1|1', 'Test_2|2', 'Test_3|3', '<hr>',
                array('Selection 1', 'Selection 2', 'Selection 3'),
                '<hr>', 'Test_4|4')
);
$menu = new Menu($vbox, $menu_definition, $accel_group);

// display title 
$title = new GtkLabel("Save a file with GtkFileChooser - Part 1");
$title->modify_font(new PangoFontDescription("Times New Roman Italic 10"));
$title->modify_fg(Gtk::STATE_NORMAL, GdkColor::parse("#0000ff"));
$vbox->pack_start($title);
$vbox->pack_start(new GtkLabel("Press Ctrl-S to save a file"));
$vbox->pack_start(new GtkLabel(""));

$window->show_all();
Gtk::main();

echo json_encode(get_declared_classes());
//echo class_exists('GladeXML')?"GladeXML OK":"NO GladeXML\n";

// class Menu 
class Menu {
    var $prev_keyval = 0;
    var $prev_state = 0;
    var $prev_keypress = '';
    function Menu($vbox, $menu_definition, $accel_group) {
        $this->menu_definition = $menu_definition;
        $menubar = new GtkMenuBar();
        $vbox->pack_start($menubar, 0, 0);
        foreach($menu_definition as $toplevel => $sublevels) {
            $top_menu = new GtkMenuItem($toplevel);
            $menubar->append($top_menu);
            $menu = new GtkMenu();
            $top_menu->set_submenu($menu);

            // let's ask php-gtk to tell us when user press the 2nd Alt key 
            $menu->connect('key-press-event', array(&$this, 'on_menu_keypress'), $toplevel);

            foreach($sublevels as $submenu) {
                if (strpos("$submenu", '|') === false) {
                    $accel_key = '';
                } else {
                    list($submenu, $accel_key) = explode('|', $submenu);
                }

                if (is_array($submenu)) { // set up radio menus 
                    $i=0;
                    $radio[0] = null;
                    foreach($submenu as $radio_item) {
                        $radio[$i] = new GtkRadioMenuItem($radio[0], $radio_item);
                        $radio[$i]->connect('toggled', array(&$this, "on_toggle"));
                        $menu->append($radio[$i]);
                        ++$i;
                    }
                    $radio[0]->set_active(1); // select the first item 
                } else {
                    if ($submenu=='<hr>') {
                        $menu->append(new GtkSeparatorMenuItem());
                    } else {
                        $submenu2 = str_replace('_', '', $submenu);
                        $submenu2 = str_replace(' ', '_', $submenu2);
                        $stock_image_name = 'Gtk::STOCK_'.strtoupper($submenu2);
                        if (defined($stock_image_name)) {
                            $menu_item = new GtkImageMenuItem(constant($stock_image_name));
                        } else {
                            $menu_item = new GtkMenuItem($submenu);
                        }
                        if ($accel_key!='') {
                            $menu_item->add_accelerator("activate", $accel_group, ord($accel_key), Gdk::CONTROL_MASK, 1);
                        }

                        $menu->append($menu_item);
                        $menu_item->connect('activate', array(&$this, 'on_menu_select'));
                        $this->menuitem[$toplevel][$submenu] = $menu_item;
                    }
                }
            }
        }
    }

    // process radio menu selection 
    function on_toggle($radio) {
        $label = $radio->child->get_label();
        $active = $radio->get_active();
        echo("radio menu selected: $label\n");
    }

    // process menu item selection 
    function on_menu_select($menu_item) {
        $item = $menu_item->child->get_label();
        echo "menu selected: $item\n";
        if (method_exists($this, $item)) $this->$item(); // note 1 
        if ($item=='E_xit') Gtk::main_quit();
    }

    // let user choose a file with a file chooser dialog 
    function _Open() { // note 3 
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
        if ($dialog->run() == Gtk::RESPONSE_OK) {
            $selected_file = $dialog->get_filename();
            echo "selected_file = $selected_file\n";
        }
  
	}
	
	}

