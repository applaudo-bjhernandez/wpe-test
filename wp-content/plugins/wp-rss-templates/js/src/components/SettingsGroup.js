import { ElementMixin, HandleDirective } from 'vue-slicksort'

/**
 * Setting Group component.
 *
 * Settings group component can be used to enable or disable some feature
 * that is described by the subset of configurations. This component doesn't
 * care about setting's configurations it only care whether the setting enabled or not.
 *
 * @return {object} The SettingsGroup component.
 */
export default {
  name: 'SettingsGroup',
  mixins: [
    ElementMixin,
  ],
  directives: {
    handle: HandleDirective
  },
  data () {
    return {
      /**
       * Whether the content of the group is opened.
       *
       * @property {boolean} isOpened
       */
      isOpened: this.canOpen && this.initialOpen,
    }
  },
  props: {
    /**
     * The title for the group.
     *
     * @property {string} title
     */
    title: {
      required: true,
      type: String,
    },
    /**
     * The description of the group.
     *
     * @property {string} description
     */
    description: {
      type: String,
    },
    /**
     * Item can be reordered.
     *
     * @property {boolean} sortable
     */
    sortable: {
      type: Boolean,
      value: false,
    },
    /**
     * Whether the group is opened on the init.
     *
     * @property {boolean} initialOpen
     */
    initialOpen: {
      type: Boolean,
      value: false,
    },
    /**
     * Whether the group can be opened.
     *
     * @property {boolean} canOpen
     */
    canOpen: {
      required: false,
      type: Boolean,
      value: true,
      default: true,
    },
    /**
     * Whether the setting is enabled.
     *
     * @property {boolean} value
     */
    value: {},
  },
  computed: {
    /**
     * Proxy for `value`.
     *
     * @property {boolean} model
     */
    model: {
      get () {
        return this.value
      },
      set (value) {
        this.$emit('input', value)
      }
    }
  },
  methods: {
    /**
     * Toggle the content of the settings group.
     */
    toggleCollapsed () {
      if (this.canOpen) {
        this.isOpened = !this.isOpened
      }
    }
  },
  render () {
    const isOpen = this.canOpen && this.isOpened;
    const classMap = {
      'wpra-settings-group': true,
      'wpra-settings-group__open': !isOpen,
      'wpra-settings-group__locked': !this.canOpen,
    };

    /*
     * Element for golding icon that represents sorting.
     */
    const sortHandle = <div
      class={{'wpra-settings-group__sort': true, 'wpra-settings-group__sort--disabled': !this.sortable}}
      v-tippy
      title={!this.sortable ? 'Position of this item cannot be changed' : null}
      v-handle
    >
        <span class={['dashicons', this.sortable ? 'dashicons-menu' : 'dashicons-lock' ]}/>
      </div>

    /*
     * Control for enabling/disabling options.
     */
    const tick = <input
      type="checkbox"
      checked={this.model}
      onClick={e => e.stopPropagation()}
      onChange={() => {this.model = !this.model}}
      class="wpra-settings-group__input"
    />

    const collapseBtn = this.canOpen && (
        <div class="wpra-settings-group__collapse">
            <span class={{
              dashicons: true,
              'dashicons-arrow-right-alt2': !isOpen,
              'dashicons-arrow-down-alt2': isOpen
            }} />
        </div>
    );

    /*
     * Passed slot fields.
     */
    const groupBody = isOpen ? <div class="wpra-settings-group__body">{ this.$slots.default }</div> : null

    return <div class={classMap}>
      <div class="wpra-settings-group__header" onClick={this.toggleCollapsed}>
        {sortHandle}
        {tick}
        <div class="wpra-settings-group__title">
          {this.title}
          <span class="wpra-settings-group__description">
            {this.description}
          </span>
        </div>
        {collapseBtn}
      </div>
      {groupBody}
    </div>
  }
}
