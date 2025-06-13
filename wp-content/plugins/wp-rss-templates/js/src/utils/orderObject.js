export const orderObjectToArray = (object) => {
  return Object.keys(object).sort((a, b) => {
    return object[a] - object[b]
  })
}

export const arrayToOrderObject = (array) => {
  return array.reduce((carry, item, index) => {
    carry[item] = index
    return carry
  }, {})
}
