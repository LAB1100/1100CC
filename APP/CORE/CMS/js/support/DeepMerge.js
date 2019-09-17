
// https://github.com/TehShrike/is-mergeable-object
// https://github.com/KyleAMathews/deepmerge

function DeepMerge() {
	
	var obj = this;
	
	var emptyTarget = function(val) {
		return Array.isArray(val) ? [] : {}
	};

	var cloneUnlessOtherwiseSpecified = function(value, options) {
		return (options.clone !== false && options.isMergeableObject(value))
			? deepmerge(emptyTarget(value), value, options)
			: value
	};

	var defaultArrayMerge = function(target, source, options) {
		return target.concat(source).map(function(element) {
			return cloneUnlessOtherwiseSpecified(element, options)
		})
	};
	
	var defaultIsMergeableObject = function(value) {
		return isNonNullObject(value)
			&& !isSpecial(value)
	};

	var isNonNullObject = function(value) {
		return !!value && typeof value === 'object'
	};

	var isSpecial = function(value) {
		var stringValue = Object.prototype.toString.call(value)

		return stringValue === '[object RegExp]'
			|| stringValue === '[object Date]'
	};
	
	var mergeObject = function(target, source, options) {
		var destination = {}
		if (options.isMergeableObject(target)) {
			Object.keys(target).forEach(function(key) {
				destination[key] = cloneUnlessOtherwiseSpecified(target[key], options)
			})
		}
		Object.keys(source).forEach(function(key) {
			if (!options.isMergeableObject(source[key]) || !target[key]) {
				destination[key] = cloneUnlessOtherwiseSpecified(source[key], options)
			} else {
				destination[key] = deepmerge(target[key], source[key], options)
			}
		})
		return destination
	};
	
	var deepmerge = function(target, source, options) {
		var options = options || {}
		options.arrayMerge = options.arrayMerge || defaultArrayMerge
		options.isMergeableObject = options.isMergeableObject || defaultIsMergeableObject

		var sourceIsArray = Array.isArray(source)
		var targetIsArray = Array.isArray(target)
		var sourceAndTargetTypesMatch = sourceIsArray === targetIsArray

		if (!sourceAndTargetTypesMatch) {
			return cloneUnlessOtherwiseSpecified(source, options)
		} else if (sourceIsArray) {
			return options.arrayMerge(target, source, options)
		} else {
			return mergeObject(target, source, options)
		}
	};

	this.single = deepmerge;

	this.all = function(array, options) {
		
		if (!Array.isArray(array)) {
			console.log('first argument should be an array')
		}

		return array.reduce(function(prev, next) {
			return deepmerge(prev, next, options)
		}, {})
	};
}
