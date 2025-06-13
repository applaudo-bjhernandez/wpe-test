import { ContainerMixin } from 'vue-slicksort'

/**
 * Sortable group component.
 *
 * Used to group things and give them ability to be reordered.
 */
export default {
  name: 'SortableGroup',
  mixins: [
    ContainerMixin
  ],
  props: {
    /**
     * Axis name for locking the sorting direction.
     *
     * @property {boolean} lockAxis
     */
    lockAxis: {
      type: String,
      default: 'y',
    },
    /**
     * Whether the drag handler should be used.
     *
     * @property {boolean} useDragHandle
     */
    useDragHandle: {
      type: Boolean,
      default: true,
    },
  },
  render () {
    return <div className="wpra-sortable-group">
      { this.$slots.default }
    </div>
  }
}
