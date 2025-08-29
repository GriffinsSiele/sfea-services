package internal

import (
	"encoding/json"
	"net/http"
)

type ParseController struct {
	AbstractController
	pdfParser *PDFParser
}

func NewParseController(pdfParser *PDFParser) *ParseController {
	return &ParseController{
		pdfParser: pdfParser,
	}
}

func (p *ParseController) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	file, info, err := r.FormFile("pdf")
	if err != nil {
		p.error(w, err, http.StatusUnprocessableEntity)
		return
	}
	//goland:noinspection GoUnhandledErrorResult
	defer file.Close()

	data, err := p.pdfParser.Parse(file, info.Size)
	if err != nil {
		p.error(w, err, http.StatusInternalServerError)
		return
	}

	//goland:noinspection GoUnhandledErrorResult
	json.NewEncoder(w).Encode(data)
}
