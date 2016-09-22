var CxenseAdmin = new function(){
    this.addWidget = function(optionKey, widgetsKey) {
        var parent = document.createElement('tr');
        var row = document.createElement('td');
        var row2 = document.createElement('td');
        var row3 = document.createElement('td');
        var input = document.createElement('input');
        var input2 = document.createElement('input');
        var input3 = document.createElement('input');
        input.style.width = "100%";
        input2.style.width = "100%";
        input3.style.width = "100%";
        input.type='text';
        input.placeholder = "Key";
        input3.placeholder = "Widget";
        input.name = optionKey + "[" + jQuery("#" + widgetsKey).children().length + "][key]";
        input.size = 10;
        input3.type = 'text';
        input3.name = optionKey + "[" + jQuery("#" + widgetsKey ).children().length + "][widget_id]";
        input2.type = 'button';
        input2.className = "button-secondary";
        input2.onclick = function(){CxenseAdmin.removeWidget(parent);};
        input2.value = 'Remove';
        row.appendChild(input2);
        parent.appendChild(row);
        row2.appendChild(input);
        parent.appendChild(row2);
        row3.appendChild(input3);
        parent.appendChild(row3);
        jQuery("#" + widgetsKey +  " table").append(parent);
    };
    this.removeWidget = function(parent){
        if(confirm("Are you sure?"))jQuery(parent).remove();
    };
};
