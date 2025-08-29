package util

import (
	"fmt"
	"regexp"
	"strconv"
	"strings"
	"unicode"
)

func CleanNonPrintable(val string) string {
	return strings.Map(func(r rune) rune {
		if unicode.IsPrint(r) {
			return r
		}

		if r == '\n' {
			return r
		}

		return ' '
	}, val)
}

func Clean(val string) string {
	return strings.TrimSpace(CleanNonPrintable(val))
}

var makeVariantsRe = regexp.MustCompile(`(?mi)([а-яё]+)\s+\(([а-яё]+)\)`)

func MakeVariants(val string) []string {
	if !makeVariantsRe.MatchString(val) {
		return []string{val}
	}

	var (
		matches       = makeVariantsRe.FindAllStringSubmatch(val, -1)
		variantsCount = len(matches) * 2
		results       = make([]string, 0, variantsCount)
	)

	for i := 0; i < variantsCount; i++ {
		var (
			treeNum        = strconv.FormatInt(int64(i), 2)
			tree           = fmt.Sprintf("%0"+strconv.Itoa(len(matches))+"s", treeNum)
			treeComponents = make([]int, len(tree))
		)

		for j := 0; j < len(tree); j++ {
			treeComponents[j], _ = strconv.Atoi(string(tree[j]))
			treeComponents[j]++
		}

		valCopy := val[:]
		for idx, component := range treeComponents {
			valCopy = strings.ReplaceAll(valCopy, matches[idx][0], matches[idx][component])
		}

		results = append(results, valCopy)
	}

	return results
}
