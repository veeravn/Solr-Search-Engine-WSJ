import java.io.File;
import java.io.FileInputStream;
import java.io.FilenameFilter;
import java.io.PrintWriter;

import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.AutoDetectParser;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.apache.tika.sax.ContentHandlerFactory;
public class Parser {

	public static void main(String[] args) throws Exception {
		File dir = new File("../../../Desktop/WSJ/WSJ");
	
		File text = new File("output/big.txt");
		text.getParentFile().mkdirs();
	
		PrintWriter writer = new PrintWriter(text);
		
		FilenameFilter filter = new FilenameFilter() {
			
			@Override
			public boolean accept(File dir, String name) {
				return !(name.length() < 10);
			}
		};
		File[] files = dir.listFiles(filter);
		
		//detecting the file type
	      BodyContentHandler handler = new BodyContentHandler(-1);
	      Metadata metadata = new Metadata();
	      int counter = 0;
	      for(File file : files) {
	    	  	counter++;
	    	  	FileInputStream inputstream = new FileInputStream(file);
	  
			BodyContentHandler contenthandler = new BodyContentHandler(-1);
			HtmlParser parser = new HtmlParser();
			parser.parse(inputstream, contenthandler, metadata, new ParseContext());
			  
			writer.println(contenthandler.toString().trim().replace("( )", " "));
			if(counter%500 ==  0) {
				System.out.println(counter + " files processed.");
			}
	      }
	      System.out.println(counter + " files processed.");
	      writer.flush();
	      writer.close();
	}
}
