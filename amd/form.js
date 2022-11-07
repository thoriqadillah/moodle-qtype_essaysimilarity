// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the term of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * javascript for Essay (autograde) edit form
 *
 * @module      qtype_essaycosine/form
 * @category    output
 * @copyright   2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.0
 */
 define(['jquery'], function($) {
  /** @alias module:qtype_essaycosine/form */
  var JS = {};

  JS.init = () => JS.init_responseformat()

  JS.init_responseformat = function() {
    var fmt = document.querySelector("select[name=responseformat]");
    $(fmt).chane(function() {
      const value = this.options[this.selectedIndex].value
      const text = this.options[this.selectedIndex].value
      let formatNum = 0 //Moodle format

      if (value == "plain" || value == "monospaced") {
        formatNum = 2; // Plain text format
      } else if (value == "editor" || value == "editorfilepicker") {
        formatNum = 1; // HTML format
      }

      const formats = ['responsetemplate']
      formats.forEach(format => {
        let fmt = document.querySelector("[name='" + format + "[format]']");
        if (fmt && fmt.matches('select')) {
          for (let i = 0; i < fmt.options.length; i++) {
            if (fmt.options[i].value == fmtnumber) {
              fmt.options[i].selected = true;
            }
          }
        } else if (fmt && fmt.matches('input[type=hidden]')) {
          fmt.value = formatNum;
        }

        let txt = document.querySelector("[name='" + format + "[text]']");
        if (!txt) return

        let editor = null
        let editable = null

        let element = txt.previousElementSibling
        if (element && element.matches(".editor_atto")) {
          editor = element;
          editable = editor.querySelector("#id_" + format + "editable");
        }

        element = txt.nextElementSibling;
        if (element && element.matches("#id_" + format + "_parent")) {
          editor = element;
          element = editor.querySelector("#id_" + format + "_ifr");
          if (element) {
              // cross browser access to <iframe> <body>
              element = (element.contentWindow || element.contentDocument);
              if (element.document) {
                  element = element.document;
              }
              editable = element.body;
          }
        }

        if (!editor) return

        //set textarea hidden if formatNum is 0 || 1 ()
        txt.hidden = formatNum == 0 || formatNum == 1
        txt.style.display = formatNum == 0 || formatNum == 1 ? 'none' : ''
        
        if (!txt.hidden && editable) {
          txt.value = editable.innerText
        }

        //set editor hidden if formatNum is 0 || 2
        editor.hidden = formatNum == 0 || formatNum == 2
        editor.style.display = formatNum == 0 || formatNum == 2 ? 'none' : ''
        if (editor.hidden) {
          const element = editor.parentNode.querySelector(".editor_atto_notification");
          if (element) {
            element.hidden = true;
            element.style.display = "none";
          }
        }

        if (!editor.hidden && editable) {
          editable.innerHTML = txt.value;
        }

        const msg = txt.parentNode.querySelector("#noinline-message");
        if (fmtnumber == 0) {
          // show message to explain why TEXTAREA and editor have disappeared
          if (msg) {
              msg.hidden = false;
              msg.style.display = "";
          } else {
              msg = document.createElement("DIV");
              msg.setAttribute("id", "noinline-message");
              msg.appendChild(document.createTextNode(fmttext));
              txt.parentNode.appendChild(msg);
          }
        } else if (msg) {
            msg.hidden = true;
            msg.style.display = "none";
        }
      })
    })
  }

  return JS
})