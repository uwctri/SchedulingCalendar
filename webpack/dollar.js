let $ = document
$.getElementByClassName = (className) => $.getElementsByClassName(className)[0]
export default $