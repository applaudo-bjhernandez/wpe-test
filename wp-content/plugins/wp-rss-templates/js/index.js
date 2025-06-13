import RenderGridTemplateOptions from '~/RenderGridTemplateOptions'
import RenderEtTemplateOptions from '~/RenderEtTemplateOptions'
import SettingsGroup from '~/components/SettingsGroup'

require('./styles/dashboard/index.scss')

window.UiFramework.registerPlugin('templates-addon-app', {
  /**
   * @param {ServicesDefinitions} services
   *
   * @return {ServicesDefinitions}
   */
  register (services) {
    services['SettingsGroup'] = () => SettingsGroup

    return services
  },

  /**
   * Run plugin.
   *
   * @param container
   */
  run ({ container }) {
    container.hooks.register('postbox-content-template-options', function (data, context) {
      if (this.model.type === 'grid') {
        return [
          RenderGridTemplateOptions.call(this, context.h, container)
        ]
      }
      if (this.model.type === 'et') {
        data.push(RenderEtTemplateOptions.call(this, context.h, container))
      }
      return data
    })
  },
})
