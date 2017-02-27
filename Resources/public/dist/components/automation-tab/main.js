define(["underscore","config","services/suluautomation/task-manager","text!./skeleton.html","text!/admin/api/tasks/fields"],function(a,b,c,d,e){"use strict";for(var f=JSON.parse(e),g=JSON.parse(e),h=b.get("sulu_security.contexts")["sulu.automation.tasks"],i=0,j=g.length;j>i;i++)"status"===g[i].name&&(g[i].disabled=!1,g[i]["default"]=!0);return{defaults:{options:{entityClass:null,locale:null,idKey:"id",notificationBadge:0},templates:{skeleton:d},translations:{headline:"sulu_automation.automation",tasks:"sulu_automation.tasks",taskHistory:"sulu_automation.task-history",successLabel:"labels.success",successMessage:"labels.success.save-desc"}},layout:{extendExisting:!0,content:{width:"fixed",leftSpace:!0,rightSpace:!0}},initialize:function(){this.entityData=this.options.data(),this.notificationBadge=this.options.notificationBadge,this.$el.append(this.templates.skeleton({translations:this.translations})),this.startTasksComponents(),this.bindCustomEvents()},bindCustomEvents:function(){this.sandbox.on("husky.datagrid.tasks.number.selections",function(a){var b="husky.toolbar.content.item.enable";0===a&&(b="husky.toolbar.content.item.disable"),this.sandbox.emit(b,"deleteSelected",!1)}.bind(this)),this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("husky.datagrid.tasks.items.get-selected",this.deleteTasksDialog.bind(this))}.bind(this)),this.sandbox.once("husky.datagrid.task-history.loaded",function(a){0!==a.total&&this.$el.find("#task-history-container").removeClass("hidden")}.bind(this)),this.sandbox.once("husky.datagrid.tasks.loaded",function(a){this.notificationBadge=a.total,this.updateNotification(this.notificationBadge)}.bind(this))},startTasksComponents:function(){var a={};h.add&&(a.add={options:{callback:this.addTask.bind(this)}}),h["delete"]&&(a.deleteSelected={});var b=[];(a.add||a.deleteSelected)&&b.push({name:"list-toolbar@suluadmin",options:{el:this.$el.find("#tasks .task-list-toolbar"),hasSearch:!1,template:this.sandbox.sulu.buttons.get(a)}}),b.push({name:"datagrid@husky",options:{el:this.$el.find("#tasks .task-list"),url:c.getUrl(this.options.entityClass,this.entityData[this.options.idKey])+"&locale="+this.options.locale+"&sortBy=schedule&sortOrder=asc&schedule=future",resultKey:"tasks",instanceName:"tasks",actionCallback:this.editTask.bind(this),viewOptions:{table:{actionIcon:h.edit?"pencil":"eye"}},matchings:f}}),b.push({name:"datagrid@husky",options:{el:this.$el.find("#task-history .task-list"),url:c.getUrl(this.options.entityClass,this.entityData[this.options.idKey])+"&locale="+this.options.locale+"&sortBy=schedule&sortOrder=desc&schedule=past",resultKey:"tasks",instanceName:"task-history",viewOptions:{table:{selectItem:!1,cssClass:"light"}},contentFilters:{status:function(a){var b="fa-question";switch(a){case"planned":b="fa-clock-o";break;case"started":b="fa-play";break;case"completed":b="fa-check-circle";break;case"failed":b="fa-ban"}return'<span class="'+b+' task-state"/>'}},matchings:g}}),this.sandbox.start(b)},editTask:function(a){var b=$("<div/>");this.$el.append(b),this.sandbox.start([{name:"automation-tab/overlay@suluautomation",options:{el:b,entityClass:this.options.entityClass,saveCallback:h.edit?this.saveTask.bind(this):null,removeCallback:h["delete"]?function(){return this.deleteTask(a)}.bind(this):null,id:a}}])},addTask:function(){var a=$("<div/>");this.$el.append(a),this.sandbox.start([{name:"automation-tab/overlay@suluautomation",options:{el:a,entityClass:this.options.entityClass,saveCallback:h.edit?this.saveTask.bind(this):null}}])},deleteTasksDialog:function(a){this.sandbox.sulu.showDeleteDialog(function(b){b&&this.deleteTasks(a)}.bind(this))},deleteTasks:function(b){return c.deleteItems(b).then(function(){a.each(b,function(a){this.decrementNotificationBadge(),this.sandbox.emit("husky.datagrid.tasks.record.remove",a),this.sandbox.emit("sulu.automation.task.remove",a)}.bind(this)),this.updateNotification(this.notificationBadge)}.bind(this))},deleteTask:function(a){return c.deleteItem(a).then(function(){this.sandbox.emit("husky.datagrid.tasks.record.remove",a),this.updateNotification(this.decrementNotificationBadge())}.bind(this))},saveTask:function(a){return a.locale=this.options.locale,a.entityClass=this.options.entityClass,a.entityId=this.entityData[this.options.idKey],c.save(a).then(function(b){var c="husky.datagrid.tasks.record.add",d="sulu.automation.task.create";a.id&&(c="husky.datagrid.tasks.records.change",d="sulu.automation.task.update"),this.sandbox.emit(c,b),this.sandbox.emit(d,b.id),this.sandbox.emit("sulu.labels.success.show",this.translations.successMessage,this.translations.successLabel),a.id||this.updateNotification(this.incrementNotificationBadge())}.bind(this))},incrementNotificationBadge:function(){return++this.notificationBadge},decrementNotificationBadge:function(){return this.notificationBadge<=0?0:--this.notificationBadge},updateNotification:function(a){this.sandbox.emit("husky.tabs.header.update-notification","tab-automation",a)}}});