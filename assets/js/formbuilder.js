(function() {
  rivets.binders.input = {
    publishes: true,
    routine: rivets.binders.value.routine,
    bind: function(el) {
      return jQuery(el).bind('input.rivets', this.publish);
    },
    unbind: function(el) {
      return jQuery(el).unbind('input.rivets');
    }
  };

  rivets.configure({
    prefix: "rv"
  });

  rivets.adapters[':'] = {
    observe: function(obj, keypath, callback) {
      return obj.on('change:' + keypath, callback);
    },
    unobserve: function(obj, keypath, callback) {
      return obj.off('change:' + keypath, callback);
    },
    get: function(obj, keypath) {
      return obj.get(keypath);
    },
    set: function(obj, keypath, value) {
      return obj.set(keypath, value);
    },
    handler: function(target, event, binding) {
      return this.call(target, event, binding.view.models);
    }
  };

  rivets.formatters.itemat = function(value, index) {
    if (!value && value instanceof Array) {
      return null;
    } else {
      return value[index || 0];
    }
  };

}).call(this);

(function() {
  var BuilderView, EditFieldView, FormSettingsView, Formbuilder, FormbuilderCollection, FormbuilderModel, ViewFieldView;

  FormbuilderModel = class FormbuilderModel extends Backbone.DeepModel {
    sync() {} // noop

    indexInDOM() {
      var $wrapper;
      $wrapper = jQuery(".fb-field-wrapper").filter(((_, el) => {
        return jQuery(el).data('cid') === this.cid;
      }));
      return jQuery(".fb-field-wrapper").index($wrapper);
    }

    is_input() {
      return Formbuilder.inputFields[this.get(Formbuilder.options.mappings.FIELD_TYPE)] != null;
    }

  };

  FormbuilderCollection = (function() {
    class FormbuilderCollection extends Backbone.Collection {
      initialize() {
        return this.on('add', this.copyCidToModel);
      }

      comparator(model) {
        return model.indexInDOM();
      }

      copyCidToModel(model) {
        return model.attributes.cid = model.cid;
      }

    };

    FormbuilderCollection.prototype.model = FormbuilderModel;

    return FormbuilderCollection;

  }).call(this);

  ViewFieldView = (function() {
    class ViewFieldView extends Backbone.View {
      initialize(options) {
        ({parentView: this.parentView} = options);
        this.listenTo(this.model, "change", this.render);
        return this.listenTo(this.model, "destroy", this.remove);
      }

      render() {
        this.$el.addClass('response-field-' + this.model.get(Formbuilder.options.mappings.FIELD_TYPE)).data('cid', this.model.cid).html(Formbuilder.templates[`view/base${!this.model.is_input() ? '_non_input' : ''}`]({
          rf: this.model
        }));
        return this;
      }

      focusEditView() {
        return this.parentView.createAndShowEditView(this.model);
      }

      clear(e) {
        var cb, x;
        e.preventDefault();
        e.stopPropagation();
        cb = () => {
          this.parentView.handleFormUpdate();
          return this.model.destroy();
        };
        x = Formbuilder.options.CLEAR_FIELD_CONFIRM;
        switch (typeof x) {
          case 'string':
            if (confirm(x)) {
              return cb();
            }
            break;
          case 'function':
            return x(cb);
          default:
            return cb();
        }
      }

      duplicate() {
        var attrs;
        attrs = _.clone(this.model.attributes);
        delete attrs['id'];
        attrs['label'] += ' Copy';
        return this.parentView.createField(attrs, {
          position: this.model.indexInDOM() + 1
        });
      }

    };

    ViewFieldView.prototype.className = "fb-field-wrapper";

    ViewFieldView.prototype.events = {
      'click .subtemplate-wrapper': 'focusEditView',
      'click .js-duplicate': 'duplicate',
      'click .js-clear': 'clear'
    };

    return ViewFieldView;

  }).call(this);

  EditFieldView = (function() {
    class EditFieldView extends Backbone.View {
      initialize(options) {
        ({parentView: this.parentView} = options);
        return this.listenTo(this.model, "destroy", this.remove);
      }

      render() {
        this.$el.html(Formbuilder.templates[`edit/base${!this.model.is_input() ? '_non_input' : ''}`]({
          rf: this.model
        }));
        rivets.bind(this.$el, {
          model: this.model
        });
        return this;
      }

      remove() {
        this.parentView.editView = void 0;
        this.parentView.$el.find("[data-target=\"#addField\"]").click();
        return super.remove(...arguments);
      }

      // @todo this should really be on the model, not the view
      addOption(e) {
        var $el, i, newOption, options;
        $el = jQuery(e.currentTarget);
        i = this.$el.find('.option').index($el.closest('.option'));
        options = this.model.get(Formbuilder.options.mappings.OPTIONS) || [];
        newOption = {
          label: "",
          checked: false
        };
        if (i > -1) {
          options.splice(i + 1, 0, newOption);
        } else {
          options.push(newOption);
        }
        this.model.set(Formbuilder.options.mappings.OPTIONS, options);
        this.model.trigger(`change:${Formbuilder.options.mappings.OPTIONS}`);
        return this.forceRender();
      }

      removeOption(e) {
        var $el, index, options;
        $el = jQuery(e.currentTarget);
        index = this.$el.find(".js-remove-option").index($el);
        options = this.model.get(Formbuilder.options.mappings.OPTIONS);
        options.splice(index, 1);
        this.model.set(Formbuilder.options.mappings.OPTIONS, options);
        this.model.trigger(`change:${Formbuilder.options.mappings.OPTIONS}`);
        return this.forceRender();
      }

      defaultUpdated(e) {
        var $el;
        $el = jQuery(e.currentTarget);
        if (this.model.get(Formbuilder.options.mappings.FIELD_TYPE) !== 'checkboxes') { // checkboxes can have multiple options selected
          this.$el.find(".js-default-updated").not($el).attr('checked', false);
        }
        this.$el.find(".js-default-updated").trigger('change');
        return this.forceRender();
      }

      forceRender() {
        return this.model.trigger('change');
      }

    };

    EditFieldView.prototype.className = "edit-response-field";

    EditFieldView.prototype.events = {
      'click .js-add-option': 'addOption',
      'click .js-remove-option': 'removeOption',
      'click .js-default-updated': 'defaultUpdated',
      'input .option-label-input': 'forceRender'
    };

    return EditFieldView;

  }).call(this);

  FormSettingsView = (function() {
    class FormSettingsView extends Backbone.View {
      initialize(options) {
        ({parentView: this.parentView} = options);
        return this.listenTo(this.model, "destroy", this.remove);
      }

      render() {
        this.$el.html(Formbuilder.templates["settings/base"]({
          rf: this.model
        }));
        rivets.bind(this.$el, {
          model: this.model
        });
        return this;
      }

      remove() {
        this.parentView.editView = void 0;
        this.parentView.$el.find("[data-target=\"#addField\"]").click();
        return super.remove(...arguments);
      }

      // @todo this should really be on the model, not the view
      addOption(e) {
        var $el, i, newOption, options;
        $el = jQuery(e.currentTarget);
        i = this.$el.find('.option').index($el.closest('.option'));
        options = this.model.get(Formbuilder.options.mappings.OPTIONS) || [];
        newOption = {
          label: "",
          checked: false
        };
        if (i > -1) {
          options.splice(i + 1, 0, newOption);
        } else {
          options.push(newOption);
        }
        this.model.set(Formbuilder.options.mappings.OPTIONS, options);
        this.model.trigger(`change:${Formbuilder.options.mappings.OPTIONS}`);
        return this.forceRender();
      }

      removeOption(e) {
        var $el, index, options;
        $el = jQuery(e.currentTarget);
        index = this.$el.find(".js-remove-option").index($el);
        options = this.model.get(Formbuilder.options.mappings.OPTIONS);
        options.splice(index, 1);
        this.model.set(Formbuilder.options.mappings.OPTIONS, options);
        this.model.trigger(`change:${Formbuilder.options.mappings.OPTIONS}`);
        return this.forceRender();
      }

      defaultUpdated(e) {
        var $el;
        $el = jQuery(e.currentTarget);
        console.log($el);
        if (this.model.get(Formbuilder.options.mappings.FIELD_TYPE) !== 'checkboxes') { // checkboxes can have multiple options selected
          this.$el.find(".js-default-updated").not($el).attr('checked', false).trigger('change');
        }
        return this.forceRender();
      }

      forceRender() {
        return this.model.trigger('change');
      }

    };

    FormSettingsView.prototype.className = "form-settings";

    FormSettingsView.prototype.events = {
      'click .js-add-option': 'addOption',
      'click .js-remove-option': 'removeOption',
      'click .js-default-updated': 'defaultUpdated',
      'input .option-label-input': 'forceRender'
    };

    return FormSettingsView;

  }).call(this);

  BuilderView = (function() {
    class BuilderView extends Backbone.View {
      initialize(options) {
        var selector;
        ({selector, formBuilder: this.formBuilder, bootstrapData: this.bootstrapData} = options);
        // This is a terrible idea because it's not scoped to this view.
        if (selector != null) {
          this.setElement(jQuery(selector));
        }
        // Create the collection, and bind the appropriate events
        this.collection = new FormbuilderCollection();
        this.collection.bind('add', this.addOne, this);
        this.collection.bind('reset', this.reset, this);
        this.collection.bind('change', this.handleFormUpdate, this);
        this.collection.bind('destroy add reset', this.hideShowNoResponseFields, this);
        this.collection.bind('destroy', this.ensureEditViewScrolled, this);
        this.render();
        this.collection.reset(this.bootstrapData);
        return this.bindSaveEvent();
      }

      bindSaveEvent() {
        this.formSaved = true;
        this.saveFormButton = this.$el.find(".js-save-form");
        this.saveFormButton.attr('disabled', true).text(Formbuilder.options.dict.ALL_CHANGES_SAVED);
        if (!!Formbuilder.options.AUTOSAVE) {
          setInterval(() => {
            return this.saveForm.call(this);
          }, 5000);
        }
        return jQuery(window).bind('beforeunload', () => {
          if (this.formSaved) {
            return void 0;
          } else {
            return Formbuilder.options.dict.UNSAVED_CHANGES;
          }
        });
      }

      reset() {
        this.$responseFields.html('');
        return this.addAll();
      }

      render() {
        var j, len, ref, subview;
        this.$el.html(Formbuilder.templates['page']());
        // Save jQuery objects for easy use
        this.$fbLeft = this.$el.find('.fb-left');
        this.$responseFields = this.$el.find('.fb-response-fields');
        // @bindWindowScrollEvent()
        this.hideShowNoResponseFields();
        ref = this.SUBVIEWS;
        for (j = 0, len = ref.length; j < len; j++) {
          subview = ref[j];
          // Render any subviews (this is an easy way of extending the Formbuilder)
          new subview({
            parentView: this
          }).render();
        }
        return this;
      }

      bindWindowScrollEvent() {
        return jQuery(window).on('scroll', () => {
          var maxMargin, newMargin;
          if (this.$fbLeft.data('locked') === true) {
            return;
          }
          newMargin = Math.max(0, jQuery(window).scrollTop() - this.$el.offset().top);
          maxMargin = this.$responseFields.height();
          return this.$fbLeft.css({
            'margin-top': Math.min(maxMargin, newMargin)
          });
        });
      }

      showTab(e) {
        var $el, first_model, target;
        $el = jQuery(e.currentTarget);
        target = $el.data('target');
        $el.closest('li').addClass('active').siblings('li').removeClass('active');
        jQuery(target).addClass('active').siblings('.fb-tab-pane').removeClass('active');
        if (target !== '#editField') {
          this.unlockLeftWrapper();
        }
        if (target === '#editField' && !this.editView && (first_model = this.collection.models[0])) {
          this.createAndShowEditView(first_model);
        }
        if (target === '#setForm') {
          return jQuery('#settings-tab').accordion({
            collapsible: true,
            heightStyle: "content"
          });
        }
      }

      addOne(responseField, _, options) {
        var $replacePosition, view;
        view = new ViewFieldView({
          model: responseField,
          parentView: this
        });
        //####
        // Calculates where to place this new field.

        // Are we replacing a temporarily drag placeholder?
        if (options.$replaceEl != null) {
          return options.$replaceEl.replaceWith(view.render().el);
        } else if ((options.position == null) || options.position === -1) {
          return this.$responseFields.append(view.render().el);
        // Are we adding to the top?
        } else if (options.position === 0) {
          return this.$responseFields.prepend(view.render().el);
        // Are we adding below an existing field?
        } else if (($replacePosition = this.$responseFields.find(".fb-field-wrapper").eq(options.position))[0]) {
          return $replacePosition.before(view.render().el);
        } else {
          // Catch-all: add to bottom
          return this.$responseFields.append(view.render().el);
        }
      }

      setSortable() {
        if (this.$responseFields.hasClass('ui-sortable')) {
          this.$responseFields.sortable('destroy');
        }
        this.$responseFields.sortable({
          forcePlaceholderSize: true,
          placeholder: 'sortable-placeholder',
          cancel: '.response-field-section_break',
          stop: (e, ui) => {
            var rf;
            if (ui.item.data('field-type')) {
              rf = this.collection.create(Formbuilder.helpers.defaultFieldAttrs(ui.item.data('field-type')), {
                $replaceEl: ui.item
              });
              this.createAndShowEditView(rf);
            }
            this.handleFormUpdate();
            return true;
          },
          update: (e, ui) => {
            var id = ui.item.attr("id");
            console.log(ui.item.index()+1);
          }
        });
        // ensureEditViewScrolled, unless we're updating from the draggable
        // @ensureEditViewScrolled() unless ui.item.data('field-type')
        return this.setDraggable();
      }

      setDraggable() {
        var $addFieldButtons;
        $addFieldButtons = this.$el.find("[data-field-type]");
        return $addFieldButtons.draggable({
          connectToSortable: this.$responseFields,
          helper: 'clone'
        });
      }

      addAll() {
        this.collection.each(this.addOne, this);
        return this.setSortable();
      }

      hideShowNoResponseFields() {
        return this.$el.find(".fb-no-response-fields")[this.collection.length > 0 ? 'hide' : 'show']();
      }

      addField(e) {
        var field_type;
        field_type = jQuery(e.currentTarget).data('field-type');
        return this.createField(Formbuilder.helpers.defaultFieldAttrs(field_type));
      }

      createField(attrs, options) {
        var rf;
        rf = this.collection.create(attrs, options);
        this.createAndShowEditView(rf);
        return this.handleFormUpdate();
      }

      createAndShowEditView(model) {
        var $newEditEl, $responseFieldEl;
        $responseFieldEl = this.$el.find(".fb-field-wrapper").filter(function() {
          return jQuery(this).data('cid') === model.cid;
        });
        $responseFieldEl.addClass('editing').siblings('.fb-field-wrapper').removeClass('editing');
        if (this.editView) {
          if (this.editView.model.cid === model.cid) {
            this.$el.find(".fb-tabs a[data-target=\"#editField\"]").click();
            this.scrollLeftWrapper($responseFieldEl);
            return;
          }
          this.editView.remove();
        }
        this.editView = new EditFieldView({
          model: model,
          parentView: this
        });
        $newEditEl = this.editView.render().$el;
        this.$el.find(".fb-edit-field-wrapper").html($newEditEl);
        this.$el.find(".fb-tabs a[data-target=\"#editField\"]").click();
        this.scrollLeftWrapper($responseFieldEl);
        return this;
      }

      ensureEditViewScrolled() {
        if (!this.editView) {
          return;
        }
        return this.scrollLeftWrapper(jQuery(".fb-field-wrapper.editing"));
      }

      scrollLeftWrapper($responseFieldEl) {
        this.unlockLeftWrapper();
        if (!$responseFieldEl[0]) {
          return;
        }
        return jQuery.scrollWindowTo((this.$el.offset().top + $responseFieldEl.offset().top) - this.$responseFields.offset().top, 200, () => {
          return this.lockLeftWrapper();
        });
      }

      lockLeftWrapper() {
        return this.$fbLeft.data('locked', true);
      }

      unlockLeftWrapper() {
        return this.$fbLeft.data('locked', false);
      }

      handleFormUpdate() {
        if (this.updatingBatch) {
          return;
        }
        this.formSaved = false;
        return this.saveFormButton.removeAttr('disabled').text(Formbuilder.options.dict.SAVE_FORM);
      }

      saveForm(e) {
        var payload;
        if (this.formSaved) {
          return;
        }
        this.formSaved = true;
        this.saveFormButton.attr('disabled', true).text(Formbuilder.options.dict.ALL_CHANGES_SAVED);
        this.collection.sort();
        payload = JSON.stringify(this.collection.toJSON());
        if (Formbuilder.options.HTTP_ENDPOINT) {
          this.doAjaxSave(payload);
        }
        return this.formBuilder.trigger('save', payload);
      }

      getUrlParameter(sParam) {
          var sPageURL = window.location.search.substring(1),
              sURLVariables = sPageURL.split('&'),
              sParameterName,
              i;

          for (i = 0; i < sURLVariables.length; i++) {
              sParameterName = sURLVariables[i].split('=');

              if (sParameterName[0] === sParam) {
                  return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
              }
          }
      };

      doAjaxSave(payload) {

        var action;

        if(this.getUrlParameter('page') === 'b60-leads') {
          action = 'save_lead_formbuilder'
        } 

        if(this.getUrlParameter('page') === 'b60-bookings') {
          action = 'save_booking_formbuilder'
        } 

        var data = {
                'action': action,
                data: payload,
                contentType: "application/json",
            }; 

        return jQuery.post(fb_ajaxurl.admin_ajaxurl, data, function(response) {
              console.log(response);
        });
        // return $.ajax({
        //   url: Formbuilder.options.HTTP_ENDPOINT,
        //   type: Formbuilder.options.HTTP_METHOD,
        //   data: payload,
        //   contentType: "application/json",
        //   success: (data) => {
        //     var datum, j, len, ref;
        //     this.updatingBatch = true;
        //     for (j = 0, len = data.length; j < len; j++) {
        //       datum = data[j];
        //       // set the IDs of new response fields, returned from the server
        //       if ((ref = this.collection.get(datum.cid)) != null) {
        //         ref.set({
        //           id: datum.id
        //         });
        //       }
        //       this.collection.trigger('sync');
        //     }
        //     return this.updatingBatch = void 0;
        //   }
        // });
      }

    };

    BuilderView.prototype.SUBVIEWS = [];

    BuilderView.prototype.events = {
      'click .js-save-form': 'saveForm',
      'click .fb-tabs a': 'showTab',
      'click .fb-add-field-types a': 'addField',
      'mouseover .fb-add-field-types': 'lockLeftWrapper',
      'mouseout .fb-add-field-types': 'unlockLeftWrapper'
    };

    return BuilderView;

  }).call(this);

  Formbuilder = (function() {
    class Formbuilder {
      static registerField(name, opts) {
        var j, len, ref, x;
        ref = ['view', 'edit'];
        for (j = 0, len = ref.length; j < len; j++) {
          x = ref[j];
          opts[x] = _.template(opts[x]);
        }
        opts.field_type = name;
        Formbuilder.fields[name] = opts;
        if (opts.type === 'non_input') {
          return Formbuilder.nonInputFields[name] = opts;
        } else {
          return Formbuilder.inputFields[name] = opts;
        }
      }

      constructor(opts = {}) {
        var args;
        _.extend(this, Backbone.Events);
        args = _.extend(opts, {
          formBuilder: this
        });
        this.mainView = new BuilderView(args);
      }

    };

    Formbuilder.helpers = {
      defaultFieldAttrs: function(field_type) {
        var attrs, base;
        attrs = {};
        attrs[Formbuilder.options.mappings.LABEL] = 'Untitled';
        attrs[Formbuilder.options.mappings.FIELD_TYPE] = field_type;
        attrs[Formbuilder.options.mappings.REQUIRED] = true;
        attrs['Formbuilder.options.mappings'] = {};
        return (typeof (base = Formbuilder.fields[field_type]).defaultAttributes === "function" ? base.defaultAttributes(attrs) : void 0) || attrs;
      },
      simple_format: function(x) {
        return x != null ? x.replace(/\n/g, '<br />') : void 0;
      }
    };

    Formbuilder.options = {
      BUTTON_CLASS: 'fb-button',
      HTTP_ENDPOINT: fb_ajaxurl.admin_ajaxurl,
      HTTP_METHOD: 'POST',
      AUTOSAVE: true,
      CLEAR_FIELD_CONFIRM: false,
      mappings: {
        SIZE: 'size',
        UNITS: 'units',
        LABEL: 'label',
        FIELD_TYPE: 'field_type',
        REQUIRED: 'required',
        ADMIN_ONLY: 'admin_only',
        OPTIONS: 'options',
        SERVICES: 'services',
        FREQUENCIES: 'frequencies',
        ADDONS: 'addons',
        PRICING: 'pricing',
        DESCRIPTION: 'description',
        INCLUDE_OTHER: 'include_other_option',
        INCLUDE_BLANK: 'include_blank_option',
        INTEGER_ONLY: 'integer_only',
        MIN: 'min',
        MAX: 'max',
        MINLENGTH: 'minlength',
        MAXLENGTH: 'maxlength',
        LENGTH_UNITS: 'min_max_length_units'
      },
      dict: {
        ALL_CHANGES_SAVED: 'All changes saved',
        SAVE_FORM: 'Save form',
        UNSAVED_CHANGES: 'You have unsaved changes. If you leave this page, you will lose those changes!'
      }
    };

    Formbuilder.fields = {};

    Formbuilder.inputFields = {};

    Formbuilder.nonInputFields = {};

    return Formbuilder;

  }).call(this);

  window.Formbuilder = Formbuilder;

  if (typeof module !== "undefined" && module !== null) {
    module.exports = Formbuilder;
  } else {
    window.Formbuilder = Formbuilder;
  }

}).call(this);

(function() {
  Formbuilder.registerField('sd_addon', {
    order: 35,
    view: `<div class="fb-addons-container"><% for (i in (rf.get(Formbuilder.options.mappings.ADDONS) || [])) { %>
  <div class="fb-addons">
    <label class='fb-option'><%= rf.get(Formbuilder.options.mappings.ADDONS)[i].label %>
    </label>
  </div>
<% } %></div>`,
    edit: `  `,
    addButton: `<span class="symbol"><span class="fa fa-link"></span></span> Extras/Addons`
  });

}).call(this);

(function() {
  Formbuilder.registerField('sd_address', {
    order: 50,
    view: `<div class='input-line'>
  <span class='street'>
    <input type='text' placeholder='Address*'/>
  </span>
  <span class='apartment'>
    <input type='text' placeholder='Apt/Suite #'/>
  </span>
</div>

<div class='input-line'>   
  <span class='city'>
    <input type='text' placeholder='City*'/>
  </span>

<span class='state'>
    <select name="state"><option value="" class="" selected="selected">State*</option><option label="AK" value="string:AK">AK</option><option label="AL" value="string:AL">AL</option><option label="AR" value="string:AR">AR</option><option label="AZ" value="string:AZ">AZ</option><option label="CA" value="string:CA">CA</option><option label="CO" value="string:CO">CO</option><option label="CT" value="string:CT">CT</option><option label="DC" value="string:DC">DC</option><option label="DE" value="string:DE">DE</option><option label="FL" value="string:FL">FL</option><option label="GA" value="string:GA">GA</option><option label="HI" value="string:HI">HI</option><option label="IA" value="string:IA">IA</option><option label="ID" value="string:ID">ID</option><option label="IL" value="string:IL">IL</option><option label="IN" value="string:IN">IN</option><option label="KS" value="string:KS">KS</option><option label="KY" value="string:KY">KY</option><option label="LA" value="string:LA">LA</option><option label="MA" value="string:MA">MA</option><option label="MD" value="string:MD">MD</option><option label="ME" value="string:ME">ME</option><option label="MI" value="string:MI">MI</option><option label="MN" value="string:MN">MN</option><option label="MO" value="string:MO">MO</option><option label="MS" value="string:MS">MS</option><option label="MT" value="string:MT">MT</option><option label="NC" value="string:NC">NC</option><option label="ND" value="string:ND">ND</option><option label="NE" value="string:NE">NE</option><option label="NH" value="string:NH">NH</option><option label="NJ" value="string:NJ">NJ</option><option label="NM" value="string:NM">NM</option><option label="NV" value="string:NV">NV</option><option label="NY" value="string:NY">NY</option><option label="OH" value="string:OH">OH</option><option label="OK" value="string:OK">OK</option><option label="OR" value="string:OR">OR</option><option label="PA" value="string:PA">PA</option><option label="RI" value="string:RI">RI</option><option label="SC" value="string:SC">SC</option><option label="SD" value="string:SD">SD</option><option label="TN" value="string:TN">TN</option><option label="TX" value="string:TX">TX</option><option label="UT" value="string:UT">UT</option><option label="VA" value="string:VA">VA</option><option label="VT" value="string:VT">VT</option><option label="WA" value="string:WA">WA</option><option label="WI" value="string:WI">WI</option><option label="WV" value="string:WV">WV</option><option label="WY" value="string:WY">WY</option></select>
  </span>
  <span class='zip'>
    <input type='text' placeholder='Zip Code*'/>
  </span>
</div>`,
    edit: "",
    addButton: `<span class="symbol"><span class="fa fa-home"></span></span> System Address`
  });

}).call(this);

(function() {
  Formbuilder.registerField('sd_calendar', {
    order: 20,
    view: `<div class='input-line'>
  <span class="symbol"><span class="fa fa-calendar"></span></span> This is the placeholder for the calendar module.
</div>`,
    edit: "",
    addButton: `<span class="symbol"><span class="fa fa-calendar"></span></span> Date`
  });

}).call(this);

(function() {
  Formbuilder.registerField('sd_customer_info', {
    order: 35,
    view: `<div class='input-line'>
  <span class='first_name'>
    <input type='text' placeholder='First Name*' />
  </span>
  <span class='last_name'>
    <input type='text' placeholder='Last Name*'/>
  </span>
</div>
<div class='input-line'>
  <span class='email'>
    <input type='text' placeholder='Email*' />
  </span>
  <span class='phone'>
    <input type='text' placeholder='Phone*'/>
  </span>
</div>`,
    edit: `  `,
    addButton: `<span class="symbol"><span class="fa fa-link"></span></span> Customer Info`
  });

}).call(this);

(function() {
  Formbuilder.registerField('sd_discount', {
    order: 35,
    view: `<input type='text' style='width:50%' placeholder='Discount code (or leave blank)' />`,
    edit: `  `,
    addButton: `<span class="symbol"><span class="fa fa-link"></span></span> Discount`
  });

}).call(this);

(function() {
  Formbuilder.registerField('sd_frequency', {
         order: 35,
         view: `<div class="fb-frequencies-container"><% for (i in (rf.get(Formbuilder.options.mappings.FREQUENCIES) || [])) { %>
  <div class="fb-frequencies">
    <label class='fb-option'><%= rf.get(Formbuilder.options.mappings.FREQUENCIES)[i].label %>
    </label>
  </div>
<% } %></div>`,
         edit: `  `,
         addButton: `<span class="symbol"><span class="fa fa-link"></span></span> Frequency`
  });

}).call(this);

(function() {
  Formbuilder.registerField('sd_service', {
    order: 35,
    view: `<div class="fb-frequencies"><select style="margin:5px;width:100%">
    <% for (i in (rf.get(Formbuilder.options.mappings.SERVICES) || [])) { %>
    <option <%= rf.get(Formbuilder.options.mappings.SERVICES)[i].checked && 'selected' %>>
      <%= rf.get(Formbuilder.options.mappings.SERVICES)[i].label %>
    </option>
  <% } %>
</select></div>
  <% for (i in (rf.get(Formbuilder.options.mappings.PRICING) || [])) { %>
    <div class="fb-frequencies"><select style="margin:5px;width:100%">
      <option <%= rf.get(Formbuilder.options.mappings.PRICING)[i].checked && 'selected' %>>
        <%= rf.get(Formbuilder.options.mappings.PRICING)[i].label %>
      </option>
    </select></div>
  <% } %>`,
    edit: `  `,
    addButton: `<span class="symbol"><span class="fa fa-link"></span></span> Service`
  });

}).call(this);

(function() {
  Formbuilder.registerField('address', {
    order: 50,
    view: `<div class='input-line'>
  <span class='street'>
    <input type='text' />
    <label>Address</label>
  </span>
</div>

<div class='input-line'>
  <span class='city'>
    <input type='text' />
    <label>City</label>
  </span>

  <span class='state'>
    <input type='text' />
    <label>State / Province / Region</label>
  </span>
</div>

<div class='input-line'>
  <span class='zip'>
    <input type='text' />
    <label>Zipcode</label>
  </span>

  <span class='country'>
    <select><option>United States</option></select>
    <label>Country</label>
  </span>
</div>`,
    edit: "",
    addButton: `<span class="symbol"><span class="fa fa-home"></span></span> Address`
  });

}).call(this);

(function() {
  Formbuilder.registerField('checkboxes', {
    order: 10,
    view: `<% for (i in (rf.get(Formbuilder.options.mappings.OPTIONS) || [])) { %>
  <div>
    <label class='fb-option'>
      <input type='checkbox' <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].checked && 'checked' %> onclick="javascript: return false;" />
      <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].label %>
    </label>
  </div>
<% } %>

<% if (rf.get(Formbuilder.options.mappings.INCLUDE_OTHER)) { %>
  <div class='other-option'>
    <label class='fb-option'>
      <input type='checkbox' />
      Other
    </label>

    <input type='text' />
  </div>
<% } %>`,
    edit: `<%= Formbuilder.templates['edit/options']({ includeOther: true }) %>`,
    addButton: `<span class="symbol"><span class="fa fa-square-o"></span></span> Checkboxes`,
    defaultAttributes: function(attrs) {
      attrs.options = [
        {
          label: "Option 1",
          checked: false
        },
        {
          label: "Option 2",
          checked: false
        }
      ];
      return attrs;
    }
  });

}).call(this);

(function() {
  Formbuilder.registerField('date', {
    order: 20,
    view: `<div class='input-line'>
  <span class='month'>
    <input type="text" />
    <label>MM</label>
  </span>

  <span class='above-line'>/</span>

  <span class='day'>
    <input type="text" />
    <label>DD</label>
  </span>

  <span class='above-line'>/</span>

  <span class='year'>
    <input type="text" />
    <label>YYYY</label>
  </span>
</div>`,
    edit: "",
    addButton: `<span class="symbol"><span class="fa fa-calendar"></span></span> Date`
  });

}).call(this);

(function() {
  Formbuilder.registerField('dropdown', {
    order: 24,
    view: `<select>
  <% if (rf.get(Formbuilder.options.mappings.INCLUDE_BLANK)) { %>
    <option value=''></option>
  <% } %>

  <% for (i in (rf.get(Formbuilder.options.mappings.OPTIONS) || [])) { %>
    <option <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].checked && 'selected' %>>
      <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].label %>
    </option>
  <% } %>
</select>`,
    edit: `<%= Formbuilder.templates['edit/options']({ includeBlank: true }) %>`,
    addButton: `<span class="symbol"><span class="fa fa-caret-down"></span></span> Dropdown`,
    defaultAttributes: function(attrs) {
      attrs.options = [
        {
          label: "",
          checked: false
        },
        {
          label: "",
          checked: false
        }
      ];
      attrs.include_blank_option = false;
      return attrs;
    }
  });

}).call(this);

(function() {
  Formbuilder.registerField('email', {
    order: 40,
    view: `<input type='text' class='rf-size-<%= rf.get(Formbuilder.options.mappings.SIZE) %>' />`,
    edit: "",
    addButton: `<span class="symbol"><span class="fa fa-envelope-o"></span></span> Email`
  });

}).call(this);

(function() {


}).call(this);

(function() {
  Formbuilder.registerField('number', {
    order: 30,
    view: `<input type='text' />
<% if (units = rf.get(Formbuilder.options.mappings.UNITS)) { %>
  <%= units %>
<% } %>`,
    edit: `<%= Formbuilder.templates['edit/min_max']() %>
<%= Formbuilder.templates['edit/units']() %>
<%= Formbuilder.templates['edit/integer_only']() %>`,
    addButton: `<span class="symbol"><span class="fa fa-number">123</span></span> Number`
  });

}).call(this);

(function() {
  Formbuilder.registerField('paragraph', {
    order: 5,
    view: `<textarea class='rf-size-<%= rf.get(Formbuilder.options.mappings.SIZE) %>'></textarea>`,
    edit: `<%= Formbuilder.templates['edit/size']() %>
<%= Formbuilder.templates['edit/min_max_length']() %>`,
    addButton: `<span class="symbol">&#182;</span> Paragraph`,
    defaultAttributes: function(attrs) {
      attrs.size = 'small';
      return attrs;
    }
  });

}).call(this);

(function() {
  Formbuilder.registerField('price', {
    order: 45,
    view: `<div class='input-line'>
  <span class='above-line'>$</span>
  <span class='dolars'>
    <input type='text' />
    <label>Dollars</label>
  </span>
  <span class='above-line'>.</span>
  <span class='cents'>
    <input type='text' />
    <label>Cents</label>
  </span>
</div>`,
    edit: "",
    addButton: `<span class="symbol"><span class="fa fa-usd"></span></span> Price`
  });

}).call(this);

(function() {
  Formbuilder.registerField('radio', {
    order: 15,
    view: `<% for (i in (rf.get(Formbuilder.options.mappings.OPTIONS) || [])) { %>
  <div>
    <label class='fb-option'>
      <input type='radio' <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].checked && 'checked' %>/>
      <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].label %>
    </label>
  </div>
<% } %>

<% if (rf.get(Formbuilder.options.mappings.INCLUDE_OTHER)) { %>
  <div class='other-option'>
    <label class='fb-option'>
      <input type='radio' />
      Other
    </label>

    <input type='text' />
  </div>
<% } %>`,
    edit: `<%= Formbuilder.templates['edit/options']({ includeOther: true }) %>`,
    addButton: `<span class="symbol"><span class="fa fa-circle-o"></span></span> Radio`,
    defaultAttributes: function(attrs) {
      // @todo
      attrs.options = [
        {
          label: "Option 1",
          checked: ""
        },
        {
          label: "Option 2",
          checked: ""
        }
      ];
      return attrs;
    }
  });

}).call(this);

(function() {
  Formbuilder.registerField('section_break', {
    order: 30,
    view: `<hr>`,
    edit: "",
    addButton: `<span class='symbol'><span class='fa fa-minus'></span></span> Section Break`
  });

}).call(this);

(function() {
  Formbuilder.registerField('text', {
    order: 0,
    view: `<input type='text' class='rf-size-<%= rf.get(Formbuilder.options.mappings.SIZE) %>' />`,
    edit: `<%= Formbuilder.templates['edit/size']() %>
<%= Formbuilder.templates['edit/min_max_length']() %>`,
    addButton: `<span class='symbol'><span class='fa fa-font'></span></span> Text`,
    defaultAttributes: function(attrs) {
      attrs.size = 'small';
      return attrs;
    }
  });

}).call(this);

(function() {
  Formbuilder.registerField('time', {
    order: 25,
    view: `<div class='input-line'>
  <span class='hours'>
    <input type="text" />
    <label>HH</label>
  </span>

  <span class='above-line'>:</span>

  <span class='minutes'>
    <input type="text" />
    <label>MM</label>
  </span>

  <span class='above-line'>:</span>

  <span class='seconds'>
    <input type="text" />
    <label>SS</label>
  </span>

  <span class='am_pm'>
    <select>
      <option>AM</option>
      <option>PM</option>
    </select>
  </span>
</div>`,
    edit: "",
    addButton: `<span class="symbol"><span class="fa fa-clock-o"></span></span> Time`
  });

}).call(this);

(function() {
  Formbuilder.registerField('website', {
    order: 35,
    view: `<input type='text' placeholder='http://' />`,
    edit: `  `,
    addButton: `<span class="symbol"><span class="fa fa-link"></span></span> Website`
  });

}).call(this);

this["Formbuilder"] = this["Formbuilder"] || {};
this["Formbuilder"]["templates"] = this["Formbuilder"]["templates"] || {};

this["Formbuilder"]["templates"]["edit/base_header"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-field-label\'>\n  <span rv-text="model:' +
((__t = ( Formbuilder.options.mappings.LABEL )) == null ? '' : __t) +
'"></span>\n  <code class=\'field-type\' rv-text=\'model:' +
((__t = ( Formbuilder.options.mappings.FIELD_TYPE )) == null ? '' : __t) +
'\'></code>\n  <span class=\'fa fa-arrow-right pull-right\'></span>\n</div>';

}
return __p
};

this["Formbuilder"]["templates"]["edit/base_non_input"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p +=
((__t = ( Formbuilder.templates['edit/base_header']() )) == null ? '' : __t) +
'\n' +
((__t = ( Formbuilder.fields[rf.get(Formbuilder.options.mappings.FIELD_TYPE)].edit({rf: rf}) )) == null ? '' : __t) +
'\n';

}
return __p
};

this["Formbuilder"]["templates"]["edit/base"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p +=
((__t = ( Formbuilder.templates['edit/base_header']() )) == null ? '' : __t) +
'\n' +
((__t = ( Formbuilder.templates['edit/common']() )) == null ? '' : __t) +
'\n' +
((__t = ( Formbuilder.fields[rf.get(Formbuilder.options.mappings.FIELD_TYPE)].edit({rf: rf}) )) == null ? '' : __t) +
'\n';

}
return __p
};

this["Formbuilder"]["templates"]["edit/checkboxes"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<label>\n  <input type=\'checkbox\' rv-checked=\'model:' +
((__t = ( Formbuilder.options.mappings.REQUIRED )) == null ? '' : __t) +
'\' />\n  Required\n</label>\n<!-- label>\n  <input type=\'checkbox\' rv-checked=\'model.' +
((__t = ( Formbuilder.options.mappings.ADMIN_ONLY )) == null ? '' : __t) +
'\' />\n  Admin only\n</label -->';

}
return __p
};

this["Formbuilder"]["templates"]["edit/class"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<input type=\'text\' rv-input=\'model:' +
((__t = ( Formbuilder.options.mappings.LABEL )) == null ? '' : __t) +
'\' />\n<textarea rv-input=\'model:' +
((__t = ( Formbuilder.options.mappings.DESCRIPTION )) == null ? '' : __t) +
'\'\n  placeholder=\'Add a longer description to this field\'></textarea>';

}
return __p
};

this["Formbuilder"]["templates"]["edit/common"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Label</div>\n\n<div class=\'fb-common-wrapper\'>\n  <div class=\'fb-label-description\'>\n    ' +
((__t = ( Formbuilder.templates['edit/label_description']() )) == null ? '' : __t) +
'\n  </div>\n  <div class=\'fb-common-checkboxes\'>\n    ' +
((__t = ( Formbuilder.templates['edit/checkboxes']() )) == null ? '' : __t) +
'\n  </div>\n  <div class=\'fb-clear\'></div>\n</div>\n';

}
return __p
};

this["Formbuilder"]["templates"]["edit/integer_only"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Integer only</div>\n<label>\n  <input type=\'checkbox\' rv-checked=\'model:' +
((__t = ( Formbuilder.options.mappings.INTEGER_ONLY )) == null ? '' : __t) +
'\' />\n  Only accept integers\n</label>\n';

}
return __p
};

this["Formbuilder"]["templates"]["edit/label_description"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<input type=\'text\' rv-value=\'model:' +
((__t = ( Formbuilder.options.mappings.LABEL )) == null ? '' : __t) +
'\'/>\n<textarea rv-value=\'model:' +
((__t = ( Formbuilder.options.mappings.DESCRIPTION )) == null ? '' : __t) +
'\'\n  placeholder=\'Add a longer description to this field\'></textarea>';

}
return __p
};

this["Formbuilder"]["templates"]["edit/min_max_length"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Length Limit</div>\n\nMin\n<input type="text" rv-value="model:' +
((__t = ( Formbuilder.options.mappings.MINLENGTH )) == null ? '' : __t) +
'" style="width: 30px" />\n\n&nbsp;&nbsp;\n\nMax\n<input type="text" rv-value="model:' +
((__t = ( Formbuilder.options.mappings.MAXLENGTH )) == null ? '' : __t) +
'" style="width: 30px" />\n\n&nbsp;&nbsp;\n\n<select rv-value="model:' +
((__t = ( Formbuilder.options.mappings.LENGTH_UNITS )) == null ? '' : __t) +
'" style="width: auto;">\n  <option value="characters">characters</option>\n  <option value="words">words</option>\n</select>';

}
return __p
};

this["Formbuilder"]["templates"]["edit/min_max"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Minimum / Maximum</div>\n\nAbove\n<input type="text" rv-input="model:' +
((__t = ( Formbuilder.options.mappings.MIN )) == null ? '' : __t) +
'" style="width: 30px" />\n\n&nbsp;&nbsp;\n\nBelow\n<input type="text" rv-input="model:' +
((__t = ( Formbuilder.options.mappings.MAX )) == null ? '' : __t) +
'" style="width: 30px" />\n';

}
return __p
};

this["Formbuilder"]["templates"]["edit/options"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Options</div>\n\n';
 if (typeof includeBlank !== 'undefined'){ ;
__p += '\n  <label>\n    <input type=\'checkbox\' rv-checked=\'model:' +
((__t = ( Formbuilder.options.mappings.INCLUDE_BLANK )) == null ? '' : __t) +
'\' />\n    Include blank\n  </label>\n';
 } ;
__p += '\n\n<div class=\'option\' rv-each-option=\'model:' +
((__t = ( Formbuilder.options.mappings.OPTIONS )) == null ? '' : __t) +
'\'>\n  <input type="checkbox" class="js-default-updated" rv-checked="option.checked"/>\n  <input type="text" rv-input="option.label" class=\'option-label-input\' style="margin:8px 0"/>\n  <a class="js-add-option ' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'" title="Add Option"><i class=\'fa fa-plus-circle\'></i></a>\n  <a class="js-remove-option ' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'" title="Remove Option"><i class=\'fa fa-minus-circle\'></i></a>\n</div>\n\n';
 if (typeof includeOther !== 'undefined'){ ;
__p += '\n  <label>\n    <input type=\'checkbox\' rv-checked=\'model:' +
((__t = ( Formbuilder.options.mappings.INCLUDE_OTHER )) == null ? '' : __t) +
'\' style="margin:8px 0"/>\n    Include "other"\n  </label>\n';
 } ;
__p += '\n\n<div class=\'fb-bottom-add\'>\n  <a class="js-add-option ' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'">Add option</a>\n</div>\n';

}
return __p
};

this["Formbuilder"]["templates"]["edit/size"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Size</div>\n<select rv-value="model:' +
((__t = ( Formbuilder.options.mappings.SIZE )) == null ? '' : __t) +
'">\n  <option value="small">Small</option>\n  <option value="medium">Medium</option>\n  <option value="large">Large</option>\n</select>\n';

}
return __p
};

this["Formbuilder"]["templates"]["edit/units"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Units</div>\n<input type="text" rv-input="model:' +
((__t = ( Formbuilder.options.mappings.UNITS )) == null ? '' : __t) +
'" />\n';

}
return __p
};

this["Formbuilder"]["templates"]["page"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p +=
((__t = ( Formbuilder.templates['partials/save_button']() )) == null ? '' : __t) +
'\n' +
((__t = ( Formbuilder.templates['partials/left_side']() )) == null ? '' : __t) +
'\n' +
((__t = ( Formbuilder.templates['partials/right_side']() )) == null ? '' : __t) +
'\n<div class=\'fb-clear\'></div>';

}
return __p
};

this["Formbuilder"]["templates"]["partials/add_field"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<div class=\'fb-tab-pane active\' id=\'addField\'>\n  <div class=\'fb-add-field-types\'>\n    <div class=\'section\'>\n      ';
 _.each(_.sortBy(Formbuilder.inputFields, 'order'), function(f){ ;
__p += '\n        <a data-field-type="' +
((__t = ( f.field_type )) == null ? '' : __t) +
'" class="' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'">\n          ' +
((__t = ( f.addButton )) == null ? '' : __t) +
'\n        </a>\n      ';
 }); ;
__p += '\n    </div>\n\n    <div class=\'section\'>\n      ';
 _.each(_.sortBy(Formbuilder.nonInputFields, 'order'), function(f){ ;
__p += '\n        <a data-field-type="' +
((__t = ( f.field_type )) == null ? '' : __t) +
'" class="' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'">\n          ' +
((__t = ( f.addButton )) == null ? '' : __t) +
'\n        </a>\n      ';
 }); ;
__p += '\n    </div>\n  </div>\n</div>\n';

}
return __p
};

this["Formbuilder"]["templates"]["partials/edit_field"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-tab-pane\' id=\'editField\'>\n  <div class=\'fb-edit-field-wrapper\'></div>\n</div>\n';

}
return __p
};

this["Formbuilder"]["templates"]["partials/left_side"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-left\'>\n  <ul class=\'fb-tabs\'>\n    <li class=\'active\'><a data-target=\'#addField\'>Add field</a></li>\n    <li><a data-target=\'#editField\'>Edit field</a></li>\n </ul>\n\n  <div class=\'fb-tab-content\'>\n    ' +
((__t = ( Formbuilder.templates['partials/add_field']() )) == null ? '' : __t) +
'\n    ' +
((__t = ( Formbuilder.templates['partials/edit_field']() )) == null ? '' : __t) +
'\n    ' +
((__t = ( Formbuilder.templates['partials/set_field']() )) == null ? '' : __t) +
'\n  </div>\n</div>';

}
return __p
};

this["Formbuilder"]["templates"]["partials/right_side"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-right\'>\n  <div class=\'fb-no-response-fields\'>No response fields</div>\n  <div class=\'fb-response-fields\'></div>\n</div>';

}
return __p
};

this["Formbuilder"]["templates"]["partials/save_button"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-save-wrapper\'>\n  <button class=\'js-save-form ' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'\'></button>\n</div>';

}
return __p
};

this["Formbuilder"]["templates"]["partials/set_field"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-tab-pane\' id=\'setForm\'>\n  <div class=\'fb-set-form-wrapper\'>\n  \t<div id=\'settings-tab\'>\n  \t  <h3>Formx Settings</h3>\n  \t  <div>\n  \t    <p>Mauris mauris ante, blandit et, ultrices a, suscipit eget, quam. Integer ut neque. Vivamus nisi metus, molestie vel, gravida in, condimentum sit amet, nunc. Nam a nibh. Donec suscipit eros. Nam mi. Proin viverra leo ut odio. Curabitur malesuada. Vestibulum a velit eu ante scelerisque vulputate.</p>\n  \t  </div>\n  \t  <h3>Appearance</h3>\n  \t  <div>\n  \t    <p>Sed non urna. Donec et ante. Phasellus eu ligula. Vestibulum sit amet purus. Vivamus hendrerit, dolor at aliquet laoreet, mauris turpis porttitor velit, faucibus interdum tellus libero ac justo. Vivamus non quam. In suscipit faucibus urna. </p>\n  \t  </div>\n  \t  <h3>Input</h3>\n  \t  <div>\n  \t    <p>Nam enim risus, molestie et, porta ac, aliquam ac, risus. Quisque lobortis. Phasellus pellentesque purus in massa. Aenean in pede. Phasellus ac libero ac tellus pellentesque semper. Sed ac felis. Sed commodo, magna quis lacinia ornare, quam ante aliquam nisi, eu iaculis leo purus venenatis dui. </p>\n  \t    <ul>\n  \t      <li>List item one</li>\n  \t      <li>List item two</li>\n  \t      <li>List item three</li>\n  \t    </ul>\n  \t  </div>\n  \t  <h3>Labels</h3>\n  \t  <div>\n  \t    <p>Cras dictum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Aenean lacinia mauris vel est. </p><p>Suspendisse eu nisl. Nullam ut libero. Integer dignissim consequat lectus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. </p>\n  \t  </div>\n  \t</div>\n  </div>\n</div>\n';

}
return __p
};

this["Formbuilder"]["templates"]["settings/base"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p +=
((__t = ( Formbuilder.templates['settings/base_header']() )) == null ? '' : __t) +
'\r\n' +
((__t = ( Formbuilder.templates['settings/common']() )) == null ? '' : __t);

}
return __p
};

this["Formbuilder"]["templates"]["settings/common"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'fb-edit-section-header\'>Label</div>\r\n\r\n<div class=\'fb-common-wrapper\'>\r\n  <div class=\'fb-label-description\'>\r\n    ' +
((__t = ( Formbuilder.templates['edit/label_description']() )) == null ? '' : __t) +
'\r\n  </div>\r\n  <div class=\'fb-common-checkboxes\'>\r\n    ' +
((__t = ( Formbuilder.templates['edit/checkboxes']() )) == null ? '' : __t) +
'\r\n  </div>\r\n  <div class=\'fb-clear\'></div>\r\n</div>\r\n';

}
return __p
};

this["Formbuilder"]["templates"]["view/base_non_input"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '';

}
return __p
};

this["Formbuilder"]["templates"]["view/base"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<div class=\'subtemplate-wrapper\'>\n  <div class=\'cover\'></div>\n  ' +
((__t = ( Formbuilder.templates['view/label']({rf: rf}) )) == null ? '' : __t) +
'\n\n  ' +
((__t = ( Formbuilder.templates['view/description']({rf: rf}) )) == null ? '' : __t) +
'\n\n\n\n  ' +
((__t = ( Formbuilder.fields[rf.get(Formbuilder.options.mappings.FIELD_TYPE)].view({rf: rf}) )) == null ? '' : __t) +
'\n\n  ';
  if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'section_break') { ;
__p += '    \n\n  ';
 } else if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'sd_frequency') {  ;
__p += '\n  \n  ';
 } else if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'sd_address') {  ;
__p += '\n  \n  ';
 } else if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'sd_addon') {  ;
__p += '\n  \n  ';
 } else if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'sd_calendar') {  ;
__p += '\n  \n  ';
 } else if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'sd_customer_info') {  ;
__p += '\n  \n  ';
 } else if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'sd_service') {  ;
__p += '\n  \n  ';
 } else if (rf.get(Formbuilder.options.mappings.FIELD_TYPE) === 'sd_discount') {  ;
__p += '\n\n  ';
 } else { ;
__p += '\n  \t' +
((__t = ( Formbuilder.templates['view/duplicate_remove']({rf: rf}) )) == null ? '' : __t) +
'\n  ';
 } ;
__p += '\n</div>\n';

}
return __p
};

this["Formbuilder"]["templates"]["view/description"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<label class=\'help-block\'>\n  ' +
((__t = ( Formbuilder.helpers.simple_format(rf.get(Formbuilder.options.mappings.DESCRIPTION)) )) == null ? '' : __t) +
'\n</label>\n';

}
return __p
};

this["Formbuilder"]["templates"]["view/duplicate_remove"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape;
with (obj) {
__p += '<div class=\'actions-wrapper\'>\n  <a class="js-duplicate ' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'" title="Duplicate Field"><i class=\'fa fa-plus-circle\'></i></a>\n  <a class="js-clear ' +
((__t = ( Formbuilder.options.BUTTON_CLASS )) == null ? '' : __t) +
'" title="Remove Field"><i class=\'fa fa-minus-circle\'></i></a>\n</div>';

}
return __p
};

this["Formbuilder"]["templates"]["view/label"] = function(obj) {
obj || (obj = {});
var __t, __p = '', __e = _.escape, __j = Array.prototype.join;
function print() { __p += __j.call(arguments, '') }
with (obj) {
__p += '<label>\n  <span>' +
((__t = ( Formbuilder.helpers.simple_format(rf.get(Formbuilder.options.mappings.LABEL)) )) == null ? '' : __t) +
'\n  ';
 if (rf.get(Formbuilder.options.mappings.REQUIRED)) { ;
__p += '\n    <abbr title=\'required\'>*</abbr>\n  ';
 } ;
__p += '\n</label>\n';

}
return __p
};